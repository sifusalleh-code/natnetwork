<?php

namespace App\Engines\Sales\Services;

use App\Engines\Billing\Models\QuotationPaymentPlan;
use App\Engines\Sales\Models\BuilderSession;
use App\Engines\Sales\Models\MasterSpecification;
use App\Engines\Sales\Models\ProjectRequest;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Scheduling\Models\SlotHold;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MasterSpecificationService
{
    public function __construct(private readonly QuotationService $quotations)
    {
    }
    public function generateDraft(BuilderSession $builder, User $customer): MasterSpecification
    {
        $this->assertOwnerAndComplete($builder, $customer);
        $builder->loadMissing(['answers.question.options', 'servicePackage']);
        $requirement = $this->requirementSnapshot($builder);

        return DB::transaction(function () use ($builder, $customer, $requirement): MasterSpecification {
            $request = ProjectRequest::query()->firstOrCreate(['builder_session_id' => $builder->id], [
                'customer_user_id' => $customer->id,
                'service_package_id' => $builder->service_package_id,
                'status' => ProjectRequest::STATUS_DRAFT,
                'requirement_snapshot' => $requirement,
            ]);
            if (Quotation::query()->where('project_request_id', $request->id)->where('status', Quotation::STATUS_ACCEPTED)->exists()) {
                throw ValidationException::withMessages(['specification' => ['Quotation projek ini telah diterima. Perubahan skop kini hanya melalui Request Change dalam portal projek.']]);
            }
            $request->update(['service_package_id' => $builder->service_package_id, 'requirement_snapshot' => $requirement]);

            $draft = $request->specifications()->where('status', MasterSpecification::STATUS_DRAFT)->latest('version')->first();
            $specification = $this->specificationSnapshot($requirement);
            if ($draft) {
                $draft->update(['requirement_snapshot' => $requirement, 'specification_snapshot' => $specification, 'technical_mapping' => $this->technicalMapping($builder)]);
                return $draft;
            }

            return $request->specifications()->create([
                'version' => ((int) $request->specifications()->max('version')) + 1,
                'status' => MasterSpecification::STATUS_DRAFT,
                'requirement_snapshot' => $requirement,
                'specification_snapshot' => $specification,
                'technical_mapping' => $this->technicalMapping($builder),
            ]);
        });
    }

    public function approve(MasterSpecification $specification, BuilderSession $builder, User $customer): MasterSpecification
    {
        $this->assertOwnerAndComplete($builder, $customer);

        return DB::transaction(function () use ($specification, $builder, $customer): MasterSpecification {
            $specification = MasterSpecification::query()->lockForUpdate()->findOrFail($specification->id);
            $specification->load('request');
            if ($specification->status !== MasterSpecification::STATUS_DRAFT || $specification->request->customer_user_id !== $customer->id) {
                throw ValidationException::withMessages(['specification' => ['Spesifikasi ini tidak boleh diluluskan.']]);
            }
            if ($builder->updated_at->greaterThan($specification->updated_at)) {
                throw ValidationException::withMessages(['specification' => ['Sila jana semula spesifikasi selepas menyimpan perubahan Builder.']]);
            }

            // Spec APPROVED lama digantikan. Quotation yang belum diterima (belum dibayar) tidak lagi sah —
            // sama seperti peraturan reset (Keputusan Owner 25 Sep 2026 #3): dipadam, bukan sekadar dikunci.
            $supersededIds = $specification->request->specifications()->where('status', MasterSpecification::STATUS_APPROVED)->pluck('id');
            if ($supersededIds->isNotEmpty()) {
                $staleQuotationIds = Quotation::query()->whereIn('master_specification_id', $supersededIds)
                    ->where('status', '!=', Quotation::STATUS_ACCEPTED)->pluck('id');
                if ($staleQuotationIds->isNotEmpty()) {
                    SlotHold::query()->whereIn('quotation_id', $staleQuotationIds)->delete();
                    QuotationPaymentPlan::query()->whereIn('quotation_id', $staleQuotationIds)->delete();
                    Quotation::query()->whereIn('id', $staleQuotationIds)->delete();
                }
            }
            $specification->request->specifications()->where('status', MasterSpecification::STATUS_APPROVED)->update(['status' => MasterSpecification::STATUS_SUPERSEDED, 'superseded_at' => now()]);
            $specification->update(['status' => MasterSpecification::STATUS_APPROVED, 'approved_by_user_id' => $customer->id, 'approved_at' => now()]);
            $this->quotations->createFromSpecification($specification->fresh('request'));

            return $specification->fresh();
        });
    }

    public function assertOwner(BuilderSession $builder, User $customer): void
    {
        if ($builder->user_id !== $customer->id) {
            abort(403);
        }
    }

    private function assertOwnerAndComplete(BuilderSession $builder, User $customer): void
    {
        $this->assertOwner($builder, $customer);
        if (! $builder->email_verified_at) {
            throw ValidationException::withMessages(['specification' => ['Email perlu disahkan sebelum spesifikasi dijana.']]);
        }
        if (! $builder->service_package_id) {
            throw ValidationException::withMessages(['specification' => ['Sila pilih pakej sebelum menjana spesifikasi.']]);
        }
        if ($builder->entry_path === 'GUIDED' && ! $builder->answers()->whereHas('question', fn ($query) => $query->where('code', 'project_type'))->exists()) {
            throw ValidationException::withMessages(['specification' => ['Sila lengkapkan jenis projek sebelum menjana spesifikasi.']]);
        }
    }

    private function requirementSnapshot(BuilderSession $builder): array
    {
        $answers = [];
        foreach ($builder->answers as $answer) {
            $value = in_array($answer->question->question_type, ['url', 'textarea', 'text'], true) ? $answer->text_value : collect($answer->value)->map(fn ($code) => $answer->question->options->firstWhere('code', $code)?->label ?? $code)->values()->all();
            if ($value !== null && $value !== [] && $value !== '') {
                $answers[] = ['code' => $answer->question->code, 'label' => $answer->question->label, 'value' => $value];
            }
        }

        $files = $builder->files()->orderBy('id')->get()->map(fn ($file) => ['id' => $file->id, 'kind' => $file->kind, 'kind_label' => $file->kindLabel(), 'name' => $file->original_name])->all();

        // Kos daripada Pricing Engine: pakej + add-on yang dipilih (disnapshot bersama spesifikasi).
        $estimate = app(\App\Engines\Pricing\Services\PriceEstimateService::class)->estimate($builder->servicePackage, app(BuilderSessionService::class)->effectiveAddonIds($builder));

        return ['entry_path' => $builder->entry_path, 'files' => $files, 'selected_package' => $builder->servicePackage ? ['name' => $builder->servicePackage->name, 'price_label' => $builder->servicePackage->price_label, 'delivery_estimate' => $builder->servicePackage->delivery_estimate] : null,
            'selected_addons' => collect($estimate['lines'])->where('kind', 'addon')->map(fn ($l) => ['id' => $l['id'], 'name' => mb_substr($l['description'], 8), 'price_label' => $l['label']])->values()->all(),
            'cost_estimate' => $estimate, 'answers' => $answers];
    }

    private function specificationSnapshot(array $requirement): array
    {
        return ['project_summary' => $requirement['answers'], 'files' => $requirement['files'] ?? [], 'selected_package' => $requirement['selected_package'], 'selected_addons' => $requirement['selected_addons'] ?? [], 'cost_estimate' => $requirement['cost_estimate'] ?? null, 'structure_and_functions' => $requirement['answers']];
    }

    private function technicalMapping(BuilderSession $builder): array
    {
        $answerCodes = $builder->answers->mapWithKeys(fn ($answer) => [$answer->question->code => $answer->value])->all();

        return ['builder_answer_codes' => $answerCodes];
    }
}
