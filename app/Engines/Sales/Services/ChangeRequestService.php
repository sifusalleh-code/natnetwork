<?php

namespace App\Engines\Sales\Services;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Billing\Services\DocumentNumberService;
use App\Engines\Identity\Models\Admin;
use App\Engines\Project\Models\Project;
use App\Engines\Sales\Events\ChangeRequestApproved;
use App\Engines\Sales\Events\ChangeRequestAssessed;
use App\Engines\Sales\Events\ChangeRequestSubmitted;
use App\Engines\Sales\Models\ChangeRequest;
use App\Engines\Sales\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Sales: Change Request. Pelanggan mohon → admin nilai (Revision dalam skop / Additional Work / tolak)
 * → Additional Work diluluskan pelanggan → Billing cipta invois ADDITIONAL_CHARGE 100% (sekali).
 */
class ChangeRequestService
{
    public function __construct(private readonly DocumentNumberService $numbers, private readonly AuditLogger $audit)
    {
    }

    public function submit(User $customer, Project $project, string $title, string $description): ChangeRequest
    {
        abort_unless((int) $project->customer_user_id === (int) $customer->id, 404);
        if ($project->isClosed() || $project->order()->value('status') !== Order::CONFIRMED) {
            throw ValidationException::withMessages(['change' => ['Permintaan perubahan hanya boleh dihantar untuk projek aktif.']]);
        }

        $cr = ChangeRequest::query()->create([
            'number' => $this->numbers->next('CR', false), 'project_id' => $project->id, 'quotation_id' => $project->quotation_id,
            'customer_user_id' => $customer->id, 'title' => $title, 'description' => $description, 'status' => ChangeRequest::SUBMITTED,
        ]);
        $this->audit->record('CHANGE_REQUEST_SUBMITTED', $customer, $cr, null, ['number' => $cr->number]);
        ChangeRequestSubmitted::dispatch($cr);

        return $cr;
    }

    /** @param 'IN_SCOPE'|'ADDITIONAL_WORK'|'DECLINE' $decision */
    public function assess(Admin $admin, ChangeRequest $cr, string $decision, ?string $note, ?string $amount, int $extraWeeks): ChangeRequest
    {
        return DB::transaction(function () use ($admin, $cr, $decision, $note, $amount, $extraWeeks): ChangeRequest {
            $cr = ChangeRequest::query()->lockForUpdate()->findOrFail($cr->id);
            if ($cr->status !== ChangeRequest::SUBMITTED) {
                throw ValidationException::withMessages(['change' => ['Hanya permintaan berstatus DIHANTAR boleh dinilai.']]);
            }
            if ($decision === 'DECLINE' && blank($note)) {
                throw ValidationException::withMessages(['admin_note' => ['Sebab wajib diisi untuk menolak permintaan.']]);
            }
            $cents = (int) round(((float) $amount) * 100);
            if ($decision === 'ADDITIONAL_WORK' && $cents < 100) {
                throw ValidationException::withMessages(['amount' => ['Harga kerja tambahan wajib diisi (minimum RM 1.00).']]);
            }

            $status = ['IN_SCOPE' => ChangeRequest::IN_SCOPE, 'ADDITIONAL_WORK' => ChangeRequest::QUOTED, 'DECLINE' => ChangeRequest::DECLINED][$decision];
            $cr->forceFill([
                'status' => $status,
                'assessment' => $decision === 'DECLINE' ? null : $decision,
                'admin_note' => $note,
                'amount' => $decision === 'ADDITIONAL_WORK' ? number_format($cents / 100, 2, '.', '') : null,
                'extra_weeks' => $decision === 'ADDITIONAL_WORK' ? max(0, $extraWeeks) : 0,
                'assessed_by_admin_id' => $admin->id,
                'assessed_at' => now(),
            ])->save();
            $this->audit->record('CHANGE_REQUEST_ASSESSED', $admin, $cr, ['status' => ChangeRequest::SUBMITTED], ['status' => $status, 'amount' => $cr->amount, 'extra_weeks' => $cr->extra_weeks], $note);
            ChangeRequestAssessed::dispatch($cr);

            return $cr;
        });
    }

    /** Pelanggan lulus/tolak sebut harga Additional Work. Idempotent. */
    public function decide(User $customer, ChangeRequest $cr, bool $approve, string $ip, string $userAgent): ChangeRequest
    {
        abort_unless((int) $cr->customer_user_id === (int) $customer->id, 404);

        $cr = DB::transaction(function () use ($customer, $cr, $approve, $ip, $userAgent): ChangeRequest {
            $cr = ChangeRequest::query()->lockForUpdate()->findOrFail($cr->id);
            if ($cr->status === ChangeRequest::APPROVED && $approve) {
                return $cr;
            }
            if ($cr->status !== ChangeRequest::QUOTED || $cr->project()->first()->isClosed()) {
                throw ValidationException::withMessages(['change' => ['Sebut harga ini tidak lagi menunggu keputusan anda.']]);
            }

            $cr->forceFill([
                'status' => $approve ? ChangeRequest::APPROVED : ChangeRequest::REJECTED_BY_CLIENT,
                'decided_at' => now(),
                'decision_metadata' => ['ip' => $ip, 'user_agent' => mb_substr($userAgent, 0, 255), 'amount' => $cr->amount, 'extra_weeks' => $cr->extra_weeks],
            ])->save();
            $this->audit->record($approve ? 'CHANGE_REQUEST_APPROVED' : 'CHANGE_REQUEST_REJECTED', $customer, $cr, ['status' => ChangeRequest::QUOTED], ['status' => $cr->status, 'amount' => $cr->amount]);

            if ($approve) {
                ChangeRequestApproved::dispatch($cr);
            }

            return $cr;
        });

        return $cr->fresh();
    }

    /** Refund projek → CR terbuka dibatalkan. */
    public function cancelOpenForProject(int $projectId): void
    {
        ChangeRequest::query()->where('project_id', $projectId)->whereIn('status', ChangeRequest::OPEN)->update(['status' => ChangeRequest::CANCELLED, 'updated_at' => now()]);
    }
}
