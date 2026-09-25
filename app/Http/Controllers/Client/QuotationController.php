<?php

namespace App\Http\Controllers\Client;

use App\Engines\Billing\Models\Invoice;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Sales\Services\QuotationWorkflowService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QuotationController extends Controller
{
    public function index(): View
    {
        $user = Auth::guard('client')->user();
        $pending = Quotation::query()->whereHas('request', fn ($q) => $q->where('customer_user_id', $user->id))
            ->where('status', Quotation::STATUS_REVIEW_REQUIRED)->count();

        return view('client.portal.quotations', ['quotations' => PortalController::quotationsFor($user)->get(), 'inReview' => $pending]);
    }

    public function show(Quotation $quotation, QuotationWorkflowService $workflow): View
    {
        $user = Auth::guard('client')->user();
        $workflow->assertOwner($quotation, $user);
        abort_if(in_array($quotation->status, [Quotation::STATUS_DRAFT, Quotation::STATUS_REVIEW_REQUIRED], true), 404);
        $workflow->markViewed($quotation, $user);

        return view('client.portal.quotation', [
            'quotation' => $quotation->fresh(),
            'depositInvoice' => Invoice::query()->where('source_type', 'Quotation')->where('source_id', $quotation->id)->where('type', 'DEPOSIT')->first(),
        ]);
    }

    public function accept(Request $request, Quotation $quotation, QuotationWorkflowService $workflow): RedirectResponse
    {
        $request->validate(['agree' => ['accepted']], ['agree.accepted' => 'Sila tanda persetujuan terhadap skop, harga dan terma sebelum menerima.']);
        $workflow->accept($quotation, Auth::guard('client')->user(), (string) $request->ip(), (string) $request->userAgent());

        return redirect()->route('client.quotations.slot', $quotation)->with('status', 'Quotation diterima. Sila pilih minggu mula projek.');
    }
}
