<?php

namespace App\Http\Controllers\Client;

use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Services\PaymentPlanService;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Sales\Services\QuotationWorkflowService;
use App\Engines\Scheduling\Services\SchedulingService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/** Pelanggan pilih minggu mula projek + cara bayar (50% / 100%) selepas quotation diterima → slot HELD → invois pertama. */
class SlotController extends Controller
{
    public function show(Quotation $quotation, QuotationWorkflowService $workflow, SchedulingService $scheduling, PaymentPlanService $plans): View|RedirectResponse
    {
        $workflow->assertOwner($quotation, Auth::guard('client')->user());
        abort_unless($quotation->status === Quotation::STATUS_ACCEPTED, 404);

        return view('client.portal.slot', [
            'quotation' => $quotation,
            'hold' => $scheduling->currentHold($quotation),
            'starts' => $scheduling->availableStarts($quotation),
            'weeks' => $scheduling->weeksFor($quotation),
            'deposit' => $this->deposit($quotation),
            'plans' => $plans->options($quotation),
        ]);
    }

    public function hold(Request $request, Quotation $quotation, QuotationWorkflowService $workflow, SchedulingService $scheduling, PaymentPlanService $plans): RedirectResponse
    {
        $workflow->assertOwner($quotation, Auth::guard('client')->user());
        abort_unless($quotation->status === Quotation::STATUS_ACCEPTED, 404);
        $locked = $plans->options($quotation)['locked'];
        $data = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'plan' => [$locked ? 'nullable' : 'required', 'in:DEPOSIT,FULL'],
        ], ['plan.required' => 'Sila pilih cara bayaran: 50% deposit atau 100% bayaran penuh.']);
        if (! $locked) {
            $plans->select($quotation, $data['plan']);
        }
        $scheduling->hold($quotation, $data['start_date']);

        $deposit = $this->deposit($quotation);

        return $deposit
            ? redirect()->route('client.billing.invoice', $deposit)->with('status', 'Slot dipegang sementara. Sila buat bayaran sebelum hold tamat untuk menempah slot.')
            : redirect()->route('client.quotations.slot', $quotation)->with('status', 'Slot dipegang sementara.');
    }

    private function deposit(Quotation $quotation): ?Invoice
    {
        return Invoice::query()->where('source_type', 'Quotation')->where('source_id', $quotation->id)->where('type', 'DEPOSIT')->first();
    }
}
