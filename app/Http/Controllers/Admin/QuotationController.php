<?php

namespace App\Http\Controllers\Admin;

use App\Engines\Affiliate\Models\AffiliateCommission;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Services\ProjectPaymentService;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Sales\Services\QuotationWorkflowService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');

        return view('admin.sales.quotations', [
            'quotations' => Quotation::query()->with('request.customer')
                ->when($status, fn ($q) => $q->where('status', $status))
                ->latest('id')->paginate(25)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(Quotation $quotation, ProjectPaymentService $projects): View
    {
        $quotation->load(['request.customer', 'specification']);
        $package = $quotation->price_snapshot['selected_package'] ?? null;
        $defaultItems = $quotation->items() ?: [[
            'description' => $package['name'] ?? 'Pembangunan projek',
            'quantity' => 1,
            'unit_price' => $package['price_amount'] ?? $quotation->total_amount ?? '',
        ]];

        return view('admin.sales.quotation', [
            'quotation' => $quotation,
            'items' => old('items', $defaultItems),
            'depositInvoice' => Invoice::query()->where('source_type', 'Quotation')->where('source_id', $quotation->id)->first(),
            'projectInvoices' => $invoices = \App\Engines\Billing\Services\ProjectPaymentService::allProjectInvoices($quotation->id),
            'summary' => $projects->summary($quotation),
            'commissions' => AffiliateCommission::query()->whereIn('invoice_id', $invoices->pluck('id'))->get()->keyBy('invoice_id'),
            'templates' => collect(config('project_templates'))->filter(fn ($t) => is_array($t) && isset($t['milestones']))->map(fn ($t) => $t['label']),
            'defaultTemplate' => $quotation->price_snapshot['project_template'] ?? $this->guessTemplate($quotation),
            'editable' => in_array($quotation->status, [Quotation::STATUS_DRAFT, Quotation::STATUS_REVIEW_REQUIRED], true) && ! $quotation->invalidated_at,
        ]);
    }

    public function send(Request $request, Quotation $quotation, QuotationWorkflowService $workflow): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:30'],
            'items.*.description' => ['required', 'string', 'max:200'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0.01', 'max:10000000'],
            'valid_until' => ['required', 'date', 'after_or_equal:today'],
            'estimated_weeks' => ['required', 'integer', 'min:1', 'max:52'],
            'project_template' => ['required', \Illuminate\Validation\Rule::in(array_keys(array_filter(config('project_templates'), fn ($t) => is_array($t) && isset($t['milestones']))))],
            'confirm' => ['accepted'],
        ], ['confirm.accepted' => 'Sila sahkan harga dan skop sebelum menghantar. Selepas dihantar, quotation dikunci.']);

        $workflow->send(Auth::guard('admin')->user(), $quotation, array_values($data['items']), $data['valid_until'], (int) $data['estimated_weeks'], $data['project_template']);

        return redirect()->route('admin.sales.quotation', $quotation)->with('status', 'Quotation dihantar kepada pelanggan.');
    }

    public function confirmPayment(Request $request, Quotation $quotation, ProjectPaymentService $projects): RedirectResponse
    {
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Sila sahkan bahawa bayaran keseluruhan projek telah selesai.']);
        $projects->confirmCompleted(Auth::guard('admin')->user(), $quotation);

        return redirect()->route('admin.sales.quotation', $quotation)->with('status', 'Bayaran projek disahkan selesai. Komisyen affiliate berkaitan telah dilepaskan.');
    }

    private function guessTemplate(Quotation $quotation): string
    {
        $package = \App\Engines\Pricing\Models\ServicePackage::query()->with('service')->find($quotation->request?->service_package_id);
        if (! $package) {
            return 'website';
        }

        return config('project_templates.package_map')[$package->slug] ?? config('project_templates.service_map')[$package->service?->slug] ?? 'website';
    }
}
