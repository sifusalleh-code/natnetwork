<?php

namespace App\Http\Controllers\Partner;

use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Services\BillplzPaymentService;
use App\Engines\Communication\Models\PortalNotification;
use App\Engines\Partnership\Models\Partner;
use App\Engines\Partnership\Models\PartnerCapital;
use App\Engines\Partnership\Models\PartnerEarning;
use App\Engines\Partnership\Models\PartnerSetting;
use App\Engines\Partnership\Services\PartnerService;
use App\Engines\Audit\Services\AuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class PartnerPortalController extends Controller
{
    public function dashboard(): View
    {
        $partner = $this->partner();
        $totalCapital = (int) round((float) PartnerCapital::query()->where('status', PartnerCapital::ACTIVE)->where('is_sandbox', false)->sum('amount') * 100);
        $mine = $partner->activeCapitalCents();

        return view('partner.dashboard', [
            'partner' => $partner,
            'settings' => PartnerSetting::current(),
            'capitalCents' => $mine,
            'sharePercent' => $totalCapital > 0 ? round($mine / $totalCapital * 100, 2) : 0,
            'earningsCents' => $partner->earningsCents(),
            'balanceCents' => $partner->balanceCents(),
            'recent' => PartnerEarning::query()->with('entry.invoice')->where('partner_id', $partner->id)->latest('id')->limit(8)->get(),
            'pending' => $partner->capitals()->where('status', PartnerCapital::PENDING_PAYMENT)->with('invoice')->first(),
        ]);
    }

    /** Langkah 3 (bayaran) dan 4 (lengkapkan profil). */
    public function onboarding(): View|RedirectResponse
    {
        $partner = $this->partner();
        $step = $partner->onboardingStep();
        if ($step === 5) {
            return redirect()->route('partner.dashboard');
        }

        return view('partner.onboarding', [
            'partner' => $partner,
            'step' => $step,
            'settings' => PartnerSetting::current(),
            'pending' => $partner->capitals()->where('status', PartnerCapital::PENDING_PAYMENT)->with('invoice')->first(),
            'banks' => PartnerAuthController::BANKS,
        ]);
    }

    public function completeProfile(Request $request, PartnerService $partners): RedirectResponse
    {
        $partner = $this->partner();
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'company_name' => [$partner->id_type === 'COMPANY' ? 'required' : 'nullable', 'string', 'max:255'],
            'id_number' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9\- ]+$/'],
            'bank_name' => ['required', Rule::in(PartnerAuthController::BANKS)],
            'bank_account_holder' => ['required', 'string', 'max:120'],
            'bank_account_number' => ['required', 'string', 'max:30', 'regex:/^[0-9\- ]{6,30}$/'],
        ]);
        $partners->completeProfile($partner, $data);

        return redirect()->route('partner.dashboard')->with('status', 'Profil lengkap. Selamat datang ke Program Partnership.');
    }

    public function capital(): View
    {
        $partner = $this->partner();

        return view('partner.capital', ['partner' => $partner, 'capitals' => $partner->capitals()->with('invoice')->get(), 'settings' => PartnerSetting::current()]);
    }

    public function storeCapital(Request $request, PartnerService $partners): RedirectResponse
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:1', 'max:10000000'], 'agree' => ['accepted']],
            ['agree.accepted' => 'Sila baca dan bersetuju dengan terma Program Partnership.']);
        $partner = $this->partner();
        $capital = $partners->contribute($partner, (string) $data['amount'], (string) $request->ip(), (string) $request->userAgent());

        return $partner->onboardingStep() === 3
            ? redirect()->route('partner.onboarding')->with('status', 'Invois modal dikeluarkan. Sila buat bayaran.')
            : redirect()->route('partner.invoice', $capital->invoice_id)->with('status', 'Invois modal dikeluarkan. Sila bayar untuk mengaktifkan modal.');
    }

    public function cancelCapital(PartnerCapital $capital, PartnerService $partners): RedirectResponse
    {
        $partners->cancelPending($this->partner(), $capital);

        return redirect()->route($this->partner()->onboardingStep() === 3 ? 'partner.onboarding' : 'partner.capital')->with('status', 'Penyertaan dibatalkan.');
    }

    public function invoice(Invoice $invoice): View
    {
        $capital = $this->authorizeInvoice($invoice);

        return view('partner.invoice', ['invoice' => $invoice->load('receipts'), 'capital' => $capital]);
    }

    public function pay(Invoice $invoice, BillplzPaymentService $payments): RedirectResponse
    {
        $capital = $this->authorizeInvoice($invoice);
        if ($capital->status !== PartnerCapital::PENDING_PAYMENT) {
            return back()->withErrors(['payment' => 'Invois ini tidak lagi boleh dibayar.']);
        }
        try {
            $payment = $payments->start($invoice);
        } catch (RuntimeException $e) {
            return redirect()->route('partner.invoice', $invoice)->withErrors(['payment' => $e->getMessage()]);
        }

        return redirect()->away($payment->gateway_url);
    }

    public function returns(): View
    {
        $partner = $this->partner();

        return view('partner.returns', [
            'partner' => $partner,
            'earnings' => PartnerEarning::query()->with('entry.invoice')->where('partner_id', $partner->id)->latest('id')->paginate(30),
            'payouts' => $partner->payouts()->latest('id')->get(),
            'balanceCents' => $partner->balanceCents(),
        ]);
    }

    public function profile(): View
    {
        return view('partner.profile', ['partner' => $this->partner(), 'banks' => PartnerAuthController::BANKS]);
    }

    public function updateProfile(Request $request, AuditLogger $audit): RedirectResponse
    {
        $partner = $this->partner();
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:50'],
            'bank_name' => ['required', Rule::in(PartnerAuthController::BANKS)],
            'bank_account_holder' => ['required', 'string', 'max:120'],
            'bank_account_number' => ['nullable', 'string', 'max:30', 'regex:/^[0-9\- ]{6,30}$/'],
        ]);
        $previous = $partner->only(['phone', 'bank_name', 'bank_account_holder', 'bank_account_last4']);
        $partner->fill(['phone' => $data['phone'], 'bank_name' => $data['bank_name'], 'bank_account_holder' => $data['bank_account_holder']]);
        if (filled($data['bank_account_number'] ?? null)) {
            $account = preg_replace('/\D+/', '', $data['bank_account_number']);
            $partner->forceFill(['bank_account_number' => $account, 'bank_account_last4' => substr($account, -4)]);
        }
        $partner->save();
        $audit->record('PARTNER_PROFILE_UPDATED', $partner, $partner, $previous, $partner->only(['phone', 'bank_name', 'bank_account_holder', 'bank_account_last4']));

        return back()->with('status', 'Profil dikemas kini.');
    }

    public function notifications(): View
    {
        return view('partner.notifications', ['notifications' => PortalNotification::query()->for('PARTNER', $this->partner()->id)->latest('id')->paginate(30)]);
    }

    public function openNotification(PortalNotification $notification): RedirectResponse
    {
        abort_unless($notification->recipient_type === 'PARTNER' && (int) $notification->recipient_id === (int) $this->partner()->id, 404);
        $notification->read_at ??= now();
        $notification->save();

        return $notification->url && str_starts_with($notification->url, url('/')) ? redirect()->to($notification->url) : redirect()->route('partner.notifications');
    }

    public function readAll(): RedirectResponse
    {
        PortalNotification::query()->for('PARTNER', $this->partner()->id)->whereNull('read_at')->update(['read_at' => now()]);

        return back();
    }

    private function authorizeInvoice(Invoice $invoice): PartnerCapital
    {
        $capital = $invoice->source_type === 'PartnerCapital' ? PartnerCapital::query()->find($invoice->source_id) : null;
        abort_unless($capital && (int) $capital->partner_id === (int) $this->partner()->id, 404);

        return $capital;
    }

    private function partner(): Partner
    {
        /** @var Partner */
        return Auth::guard('partner')->user();
    }
}
