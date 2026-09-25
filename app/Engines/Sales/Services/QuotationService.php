<?php

namespace App\Engines\Sales\Services;

use App\Engines\Pricing\Models\ServicePackage;
use App\Engines\Pricing\Services\PriceEstimateService;
use App\Engines\Sales\Models\MasterSpecification;
use App\Engines\Sales\Models\Quotation;

class QuotationService
{
    public function __construct(private readonly PriceEstimateService $pricing, private readonly QuotationWorkflowService $workflow)
    {
    }

    public function createFromSpecification(MasterSpecification $specification): Quotation
    {
        $existing = Quotation::query()->where('master_specification_id', $specification->id)->first();
        if ($existing) {
            return $existing;
        }

        $package = ServicePackage::query()->find($specification->request->service_package_id);
        $snapshot = [
            'master_specification_version' => $specification->version,
            'selected_package' => $package ? [
                'name' => $package->name,
                'scope_summary' => $package->summary,
                'price_type' => $package->price_type,
                'price_amount' => $package->price_amount,
                'price_label' => $package->price_label,
                'delivery_estimate' => $package->delivery_estimate,
            ] : null,
            'status_reason' => 'Commercial review is required before quotation terms, validity and acceptance are available.',
        ];

        $quotation = Quotation::query()->create([
            'project_request_id' => $specification->project_request_id,
            'master_specification_id' => $specification->id,
            'status' => Quotation::STATUS_REVIEW_REQUIRED,
            'price_snapshot' => $snapshot,
            'terms_snapshot' => config('sales_terms'),
            'total_amount' => $package?->price_type === 'fixed' ? $package->price_amount : null,
        ]);

        // Kos jelas (pakej tetap/permulaan + add-on): quotation dijana & dihantar terus kepada pelanggan.
        // Pakej "quote"/bulanan kekal REVIEW REQUIRED untuk semakan admin.
        $estimate = $this->pricing->estimate($package, collect($specification->requirement_snapshot['selected_addons'] ?? [])->pluck('id')->all());
        if ($package && ! $estimate['needs_review'] && $estimate['total_cents'] > 0) {
            $items = collect($estimate['lines'])->map(fn ($l) => ['description' => $l['description'], 'quantity' => 1, 'unit_price' => number_format($l['cents'] / 100, 2, '.', '')])->all();
            $template = config('project_templates.package_map.'.$package->slug) ?? config('project_templates.service_map.'.$package->service?->slug) ?? 'website';

            return $this->workflow->send(null, $quotation, $items, now()->addDays((int) config('billing.auto_quotation_valid_days', 14))->toDateString(), $this->pricing->estimatedWeeks($package), $template);
        }

        return $quotation;
    }
}
