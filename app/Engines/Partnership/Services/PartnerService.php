<?php

namespace App\Engines\Partnership\Services;

use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Services\DocumentNumberService;
use App\Engines\Billing\Services\InvoiceService;
use App\Engines\Communication\Services\NotificationService;
use App\Engines\Identity\Models\Admin;
use App\Engines\Partnership\Models\Partner;
use App\Engines\Partnership\Models\PartnerCapital;
use App\Engines\Partnership\Models\PartnerPayout;
use App\Engines\Partnership\Models\PartnerSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Partner Engine: akaun, semakan (KYC), modal, pengeluaran pulangan. */
class PartnerService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly InvoiceService $invoices,
        private readonly DocumentNumberService $numbers,
        private readonly NotificationService $notify,
    ) {
    }

    /**
     * Langkah 3: pendaftaran asas selepas emel disahkan & terma dipersetujui. Akaun terus aktif (tiada semakan admin);
     * KYC (IC & bank) dilengkapkan selepas bayaran modal berjaya.
     *
     * @param array{name: string, email: string, phone: string, id_type: string, company_name?: ?string, terms_ip?: ?string, terms_user_agent?: ?string} $data
     */
    public function register(array $data): Partner
    {
        return DB::transaction(function () use ($data): Partner {
            $partner = Partner::query()->where('email', $data['email'])->first();
            if ($partner) {
                return $partner;
            }
            $partner = Partner::query()->create([
                'name' => $data['name'], 'email' => $data['email'], 'phone' => $data['phone'],
                'id_type' => $data['id_type'], 'id_number' => null,
                'company_name' => $data['id_type'] === 'COMPANY' ? ($data['company_name'] ?? null) : null,
                'email_verified_at' => now(), 'terms_accepted_at' => now(), 'status' => Partner::APPROVED,
            ]);
            $this->audit->record('PARTNER_REGISTERED', $partner, $partner, null, [
                'email' => $partner->email, 'terms_version' => config('partnership_terms.version'),
                'ip' => $data['terms_ip'] ?? null, 'user_agent' => isset($data['terms_user_agent']) ? mb_substr($data['terms_user_agent'], 0, 255) : null,
            ]);

            return $partner;
        });
    }

    /**
     * Langkah 4: lengkapkan profil (KYC) selepas modal pertama dibayar. Membuka akses Dashboard.
     *
     * @param array{phone: string, id_number: string, bank_name: string, bank_account_holder: string, bank_account_number: string, company_name?: ?string} $data
     */
    public function completeProfile(Partner $partner, array $data): Partner
    {
        if (! $partner->hasPaidCapital()) {
            throw ValidationException::withMessages(['profile' => ['Sila selesaikan bayaran modal terlebih dahulu.']]);
        }
        $idNumber = preg_replace('/\s+/', '', $data['id_number']);
        $account = preg_replace('/\D+/', '', $data['bank_account_number']);
        $previous = $partner->only(['phone', 'bank_name', 'bank_account_holder', 'bank_account_last4', 'id_last4']);
        $partner->forceFill([
            'phone' => $data['phone'],
            'company_name' => $partner->id_type === 'COMPANY' ? ($data['company_name'] ?? $partner->company_name) : null,
            'id_number' => $idNumber, 'id_last4' => substr($idNumber, -4),
            'bank_name' => $data['bank_name'], 'bank_account_holder' => $data['bank_account_holder'],
            'bank_account_number' => $account, 'bank_account_last4' => substr($account, -4),
            'profile_completed_at' => $partner->profile_completed_at ?? now(),
        ])->save();
        $this->audit->record('PARTNER_PROFILE_COMPLETED', $partner, $partner, $previous, $partner->only(['phone', 'bank_name', 'bank_account_holder', 'bank_account_last4', 'id_last4']));

        return $partner;
    }

    public function review(Admin $admin, Partner $partner, string $decision, ?string $note): Partner
    {
        $to = ['approve' => Partner::APPROVED, 'reject' => Partner::REJECTED, 'suspend' => Partner::SUSPENDED][$decision] ?? null;
        if (! $to) {
            throw ValidationException::withMessages(['decision' => ['Keputusan tidak sah.']]);
        }
        if ($to !== Partner::APPROVED && blank($note)) {
            throw ValidationException::withMessages(['review_note' => ['Sebab wajib diisi.']]);
        }

        $from = $partner->status;
        $partner->forceFill(['status' => $to, 'review_note' => $note, 'reviewed_by_admin_id' => $admin->id, 'reviewed_at' => now()])->save();
        $this->audit->record('PARTNER_REVIEWED', $admin, $partner, ['status' => $from], ['status' => $to], $note);
        $this->notify->toPartner($partner->id, 'ACCOUNT', 'Status akaun partner: '.$partner->label(), $note ?: ($to === Partner::APPROVED ? 'Anda kini boleh menyertai program dengan menambah modal.' : null), route('partner.dashboard'));

        return $partner;
    }

    /** Partner tambah modal → invois modal (PARTNER_CAPITAL) dibayar melalui Billplz. Modal aktif selepas PAID. */
    public function contribute(Partner $partner, string $amount, string $ip, string $userAgent): PartnerCapital
    {
        $settings = PartnerSetting::current();
        if (! $settings->program_enabled) {
            throw ValidationException::withMessages(['amount' => ['Program Partnership belum dibuka.']]);
        }
        if (! $partner->isApproved()) {
            throw ValidationException::withMessages(['amount' => ['Akaun anda tidak aktif. Sila hubungi kami.']]);
        }
        $cents = (int) round(((float) $amount) * 100);
        $min = (int) round((float) $settings->min_capital * 100);
        if ($cents < $min) {
            throw ValidationException::withMessages(['amount' => ['Modal minimum ialah RM '.number_format($min / 100, 2).'.']]);
        }
        $committed = (int) round((float) PartnerCapital::query()->whereIn('status', [PartnerCapital::ACTIVE, PartnerCapital::PENDING_PAYMENT])->where('is_sandbox', false)->sum('amount') * 100);
        $cap = (int) round((float) $settings->max_total_capital * 100);
        if ($committed + $cents > $cap) {
            throw ValidationException::withMessages(['amount' => ['Had kumpulan modal hampir penuh. Baki yang boleh disertai: RM '.number_format(max(0, $cap - $committed) / 100, 2).'.']]);
        }
        if (PartnerCapital::query()->where('partner_id', $partner->id)->where('status', PartnerCapital::PENDING_PAYMENT)->exists()) {
            throw ValidationException::withMessages(['amount' => ['Sila selesaikan atau batalkan penyertaan yang menunggu bayaran dahulu.']]);
        }

        return DB::transaction(function () use ($partner, $cents, $ip, $userAgent): PartnerCapital {
            $contribution = PartnerCapital::query()->create([
                'number' => $this->numbers->next('PTC', false),
                'partner_id' => $partner->id,
                'amount' => number_format($cents / 100, 2, '.', ''),
                'status' => PartnerCapital::PENDING_PAYMENT,
                'terms_snapshot' => config('partnership_terms'),
                'acceptance_metadata' => ['ip' => $ip, 'user_agent' => mb_substr($userAgent, 0, 255), 'accepted_at' => now()->toIso8601String()],
            ]);
            $invoice = $this->invoices->issue('PARTNER_CAPITAL', [
                'name' => $partner->company_name ?: $partner->name, 'email' => $partner->email, 'phone' => $partner->phone, 'company' => $partner->company_name,
            ], [['description' => 'Modal Program Partnership '.$contribution->number, 'quantity' => 1, 'unit_price' => $contribution->amount]], null, null, 'PartnerCapital', $contribution->id);
            $contribution->forceFill(['invoice_id' => $invoice->id, 'is_sandbox' => $invoice->is_sandbox])->save();
            $this->audit->record('PARTNER_CAPITAL_CREATED', $partner, $contribution, null, ['amount' => $contribution->amount, 'invoice' => $invoice->number]);

            return $contribution;
        });
    }

    public function cancelPending(Partner $partner, PartnerCapital $contribution): void
    {
        abort_unless((int) $contribution->partner_id === (int) $partner->id, 404);
        DB::transaction(function () use ($contribution): void {
            $contribution = PartnerCapital::query()->lockForUpdate()->findOrFail($contribution->id);
            $invoice = Invoice::query()->lockForUpdate()->find($contribution->invoice_id);
            if ($contribution->status !== PartnerCapital::PENDING_PAYMENT || ($invoice && (float) $invoice->amount_paid > 0)) {
                throw ValidationException::withMessages(['capital' => ['Penyertaan ini tidak lagi boleh dibatalkan.']]);
            }
            $contribution->forceFill(['status' => PartnerCapital::CANCELLED])->save();
            $invoice?->forceFill(['status' => Invoice::STATUS_VOID, 'voided_at' => now()])->save();
        });
    }

    /** Callback Billplz: invois modal PAID → modal ACTIVE. Idempotent. */
    public function activateForInvoice(Invoice $invoice): void
    {
        if ($invoice->source_type !== 'PartnerCapital' || $invoice->status !== Invoice::STATUS_PAID) {
            return;
        }
        $contribution = PartnerCapital::query()->lockForUpdate()->find($invoice->source_id);
        if (! $contribution || $contribution->status === PartnerCapital::ACTIVE) {
            return;
        }
        $contribution->forceFill(['status' => PartnerCapital::ACTIVE, 'activated_at' => now()])->save();
        $this->audit->record('PARTNER_CAPITAL_ACTIVATED', null, $contribution, ['status' => PartnerCapital::PENDING_PAYMENT], ['status' => PartnerCapital::ACTIVE]);
        $this->notify->toPartner($contribution->partner_id, 'CAPITAL', 'Modal RM '.number_format((float) $contribution->amount, 2).' kini aktif', 'Bayaran '.$invoice->number.' disahkan. Anda layak menerima bahagian pool bagi jualan seterusnya.', route('partner.capital'));
    }

    public function recordPayout(Admin $admin, Partner $partner, string $amount, string $reference, ?string $note): PartnerPayout
    {
        return DB::transaction(function () use ($admin, $partner, $amount, $reference, $note): PartnerPayout {
            $partner = Partner::query()->lockForUpdate()->findOrFail($partner->id);
            $cents = (int) round(((float) $amount) * 100);
            if ($cents < 1 || $cents > $partner->balanceCents()) {
                throw ValidationException::withMessages(['amount' => ['Amaun mesti antara RM 0.01 dan baki semasa (RM '.number_format($partner->balanceCents() / 100, 2).').']]);
            }
            $payout = PartnerPayout::query()->create([
                'number' => $this->numbers->next('PPO', false), 'partner_id' => $partner->id, 'amount' => number_format($cents / 100, 2, '.', ''),
                'transfer_reference' => $reference, 'note' => $note, 'paid_by_admin_id' => $admin->id, 'paid_at' => now(),
            ]);
            $this->audit->record('PARTNER_PAYOUT_RECORDED', $admin, $payout, null, ['amount' => $payout->amount, 'reference' => $reference], $note);
            $this->notify->toPartner($partner->id, 'PAYOUT', 'Pulangan RM '.number_format($cents / 100, 2).' telah dipindahkan', 'Rujukan: '.$reference, route('partner.returns'));

            return $payout;
        });
    }
}
