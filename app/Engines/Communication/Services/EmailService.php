<?php

namespace App\Engines\Communication\Services;

use App\Adapters\Resend\ResendClient;
use App\Engines\Communication\Models\CommunicationSetting;
use App\Engines\Communication\Models\EmailLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Communication: pemilik tunggal penghantaran emel.
 * Resend aktif (Admin → Tetapan Emel) → hantar melalui Resend; jika tidak → mailer Laravel (.env, cth. log).
 * Kegagalan emel tidak pernah menggagalkan proses perniagaan; setiap percubaan dilog (tanpa kandungan).
 */
class EmailService
{
    public function __construct(private readonly ResendClient $resend)
    {
    }

    /** Hantar selepas transaksi DB semasa commit (jika ada), supaya emel tidak keluar untuk rekod yang di-rollback. */
    public function queueAfterCommit(string $to, string $subject, string $text, string $category): void
    {
        DB::afterCommit(fn () => $this->send($to, $subject, $text, $category));
    }

    /** Hantar serta-merta. Pulangkan true jika berjaya. $throw untuk kes yang perlu tahu kegagalan (OTP, emel ujian). */
    public function send(string $to, string $subject, string $text, string $category, bool $throw = false, ?string $replyTo = null): bool
    {
        $settings = CommunicationSetting::current();
        $channel = $settings->resendReady() ? 'RESEND' : 'MAILER';
        $body = $text."\n\n—\n".config('company.name', 'NatNetwork Synergy')."\n".url('/');

        try {
            $providerId = null;
            if ($channel === 'RESEND') {
                $providerId = $this->resend->send($settings->resend_api_key, $settings->fromHeader(), $to, $subject, $body, null, $replyTo);
            } else {
                if (! self::mailerDelivers()) {
                    // Mailer "log" hanya menulis ke storage/logs — emel TIDAK sampai kepada penerima.
                    throw new EmailNotConfigured('Tiada penghantar emel aktif: Resend belum diaktifkan dan MAIL_MAILER='.config('mail.default').'.');
                }
                Mail::raw($body, function ($message) use ($to, $subject, $replyTo) {
                    $message->to($to)->subject($subject);
                    if ($replyTo) {
                        $message->replyTo($replyTo);
                    }
                });
            }
            $this->log($to, $subject, $category, $channel, 'SENT', $providerId, null);

            return true;
        } catch (Throwable $e) {
            $this->log($to, $subject, $category, $channel, 'FAILED', null, $e->getMessage());
            report($e);
            if ($throw) {
                throw $e;
            }

            return false;
        }
    }

    /** Adakah mailer Laravel (.env) benar-benar menghantar emel? "log" tidak. ("array" hanya untuk ujian.) */
    public static function mailerDelivers(): bool
    {
        return config('mail.default') !== 'log';
    }

    public static function deliveryReady(): bool
    {
        return CommunicationSetting::current()->resendReady() || self::mailerDelivers();
    }

    private function log(string $to, string $subject, string $category, string $channel, string $status, ?string $providerId, ?string $error): void
    {
        try {
            EmailLog::query()->create([
                'recipient' => $to, 'subject' => mb_substr($subject, 0, 250), 'category' => $category, 'channel' => $channel,
                'status' => $status, 'provider_id' => $providerId, 'error' => $error ? mb_substr($error, 0, 490) : null, 'created_at' => now(),
            ]);
        } catch (Throwable) {
            // Log emel tidak boleh menggagalkan penghantaran.
        }
    }
}
