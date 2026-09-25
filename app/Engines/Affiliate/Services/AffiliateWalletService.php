<?php

namespace App\Engines\Affiliate\Services;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliateCommission;
use App\Engines\Affiliate\Models\AffiliateWithdrawal;
use App\Engines\Audit\Services\AuditLogger;
use App\Engines\Identity\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Baki wallet = komisen RELEASED − withdrawal (Dalam proses + Dibayar). Semua kiraan dalam sen, di server.
 * Withdrawal diproses manual oleh admin (transfer bank), kemudian ditanda Dibayar dengan rujukan.
 */
class AffiliateWalletService
{
    public function __construct(private readonly AffiliateWeeklyTaskService $tasks, private readonly AuditLogger $audit)
    {
    }

    /** @return array{pending: string, released: string, withdrawn: string, processing: string, available: string} */
    public function summary(Affiliate $affiliate): array
    {
        $commission = fn (string $status) => $this->cents(AffiliateCommission::query()->where('affiliate_id', $affiliate->id)->where('status', $status)->sum('amount'));
        $withdrawal = fn (string $status) => $this->cents(AffiliateWithdrawal::query()->where('affiliate_id', $affiliate->id)->where('status', $status)->sum('amount'));

        $released = $commission(AffiliateCommission::STATUS_RELEASED);
        $paid = $withdrawal(AffiliateWithdrawal::STATUS_PAID);
        $processing = $withdrawal(AffiliateWithdrawal::STATUS_REQUESTED);

        return [
            'pending' => $this->money($commission(AffiliateCommission::STATUS_PENDING)),
            'released' => $this->money($released),
            'withdrawn' => $this->money($paid),
            'processing' => $this->money($processing),
            'available' => $this->money(max(0, $released - $paid - $processing)),
        ];
    }

    public function request(Affiliate $affiliate, string $amount): AffiliateWithdrawal
    {
        return DB::transaction(function () use ($affiliate, $amount): AffiliateWithdrawal {
            $affiliate = Affiliate::query()->lockForUpdate()->findOrFail($affiliate->id);
            $cents = $this->cents($amount);

            if ($cents <= 0) {
                throw ValidationException::withMessages(['amount' => ['Amaun mesti lebih daripada RM0.']]);
            }
            if (AffiliateWithdrawal::query()->where('affiliate_id', $affiliate->id)->where('status', AffiliateWithdrawal::STATUS_REQUESTED)->exists()) {
                throw ValidationException::withMessages(['amount' => ['Anda masih mempunyai permohonan withdrawal yang sedang diproses.']]);
            }
            if (! $affiliate->bank_name || ! $affiliate->bank_account_number) {
                throw ValidationException::withMessages(['amount' => ['Sila lengkapkan maklumat bank dalam Profil Saya terlebih dahulu.']]);
            }
            $task = $this->tasks->progress($affiliate);
            if (! $task['met']) {
                throw ValidationException::withMessages(['amount' => ["Task mingguan belum lengkap: {$task['shares']}/{$task['share_target']} perkongsian dan {$task['clicks']}/{$task['click_target']} unique clicks."]]);
            }
            $available = $this->cents($this->summary($affiliate)['available']);
            if ($cents > $available) {
                throw ValidationException::withMessages(['amount' => ['Amaun melebihi baki yang boleh dikeluarkan ('.$this->label($available).').']]);
            }

            $withdrawal = AffiliateWithdrawal::query()->create([
                'affiliate_id' => $affiliate->id,
                'amount' => $this->money($cents),
                'status' => AffiliateWithdrawal::STATUS_REQUESTED,
                'bank_name' => $affiliate->bank_name,
                'bank_account_number' => $affiliate->bank_account_number,
                'bank_account_last4' => $affiliate->bank_account_last4,
                'account_holder' => $affiliate->name,
                'week_shares' => $task['shares'],
                'week_unique_clicks' => $task['clicks'],
                'requested_at' => now(),
            ]);
            $this->audit->record('AFFILIATE_WITHDRAWAL_REQUESTED', $affiliate, $withdrawal, null, ['amount' => $withdrawal->amount]);

            return $withdrawal;
        });
    }

    public function markPaid(Admin $admin, AffiliateWithdrawal $withdrawal, string $reference): AffiliateWithdrawal
    {
        return $this->process($admin, $withdrawal, AffiliateWithdrawal::STATUS_PAID, ['reference' => $reference]);
    }

    public function reject(Admin $admin, AffiliateWithdrawal $withdrawal, string $reason): AffiliateWithdrawal
    {
        return $this->process($admin, $withdrawal, AffiliateWithdrawal::STATUS_REJECTED, ['note' => $reason]);
    }

    private function process(Admin $admin, AffiliateWithdrawal $withdrawal, string $status, array $extra): AffiliateWithdrawal
    {
        return DB::transaction(function () use ($admin, $withdrawal, $status, $extra): AffiliateWithdrawal {
            $locked = AffiliateWithdrawal::query()->lockForUpdate()->findOrFail($withdrawal->id);
            if ($locked->status !== AffiliateWithdrawal::STATUS_REQUESTED) {
                throw ValidationException::withMessages(['withdrawal' => ['Permohonan ini telah diproses.']]);
            }
            $locked->forceFill($extra + ['status' => $status, 'processed_by_admin_id' => $admin->id, 'processed_at' => now()])->save();
            $this->audit->record('AFFILIATE_WITHDRAWAL_'.$status, $admin, $locked, ['status' => AffiliateWithdrawal::STATUS_REQUESTED], ['status' => $status] + $extra);

            return $locked;
        });
    }

    private function cents(mixed $value): int { return (int) round(((float) $value) * 100); }
    private function money(int $cents): string { return number_format($cents / 100, 2, '.', ''); }
    private function label(int $cents): string { return 'RM'.number_format($cents / 100, 2); }
}
