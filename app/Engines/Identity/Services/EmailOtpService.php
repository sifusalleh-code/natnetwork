<?php

namespace App\Engines\Identity\Services;

use App\Adapters\Email\OtpMailSender;
use App\Engines\Identity\Models\EmailOtpChallenge;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class EmailOtpService
{
    public function __construct(private readonly OtpMailSender $mailSender)
    {
    }

    public function issue(string $email, string $ipAddress, string $purpose = EmailOtpChallenge::PURPOSE_CLIENT_LOGIN): void
    {
        $email = $this->normaliseEmail($email);
        $rateKey = $this->rateKey($email, $ipAddress, $purpose);

        if (RateLimiter::tooManyAttempts($rateKey, config('identity.otp.max_requests'))) {
            throw new OtpRateLimited(RateLimiter::availableIn($rateKey));
        }

        $latest = EmailOtpChallenge::query()->where('email', $email)->where('purpose', $purpose)->whereNull('consumed_at')->whereNull('invalidated_at')->latest('sent_at')->first();
        if ($latest?->sent_at?->greaterThan(now()->subSeconds(config('identity.otp.resend_cooldown_seconds')))) {
            throw new OtpRateLimited(now()->diffInSeconds($latest->sent_at->copy()->addSeconds(config('identity.otp.resend_cooldown_seconds'))));
        }

        $code = str_pad((string) random_int(0, (10 ** config('identity.otp.length')) - 1), config('identity.otp.length'), '0', STR_PAD_LEFT);
        $challenge = DB::transaction(function () use ($email, $code, $purpose): EmailOtpChallenge {
            EmailOtpChallenge::query()->where('email', $email)->where('purpose', $purpose)->whereNull('consumed_at')->whereNull('invalidated_at')->update(['invalidated_at' => now()]);

            return EmailOtpChallenge::query()->create([
                'user_id' => $purpose === EmailOtpChallenge::PURPOSE_CLIENT_LOGIN ? User::query()->where('email', $email)->value('id') : null, 'email' => $email,
                'purpose' => $purpose, 'code_hash' => Hash::make($code), 'attempts' => 0,
                'sent_at' => now(), 'expires_at' => now()->addMinutes(config('identity.otp.expires_after_minutes')),
            ]);
        });

        RateLimiter::hit($rateKey, config('identity.otp.request_decay_seconds'));

        try { $this->mailSender->send($email, $code); } catch (Throwable $exception) {
            // Kod tidak sampai: batalkan kod ini supaya pengguna boleh cuba semula serta-merta, dan papar mesej jelas (bukan ralat 500).
            $challenge->forceFill(['invalidated_at' => now()])->save();
            throw ValidationException::withMessages(['email' => ['Kod tidak dapat dihantar ke emel anda sekarang. Sila cuba sebentar lagi atau hubungi kami di '.config('company.email').'.']]);
        }
    }

    public function verify(string $email, string $code): User
    {
        $email = $this->normaliseEmail($email);
        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            throw new OtpVerificationFailed('Kod tidak sah atau telah tamat tempoh.');
        }

        $this->consume($email, $code);
        if (! $user->email_verified_at) { $user->forceFill(['email_verified_at' => now()])->save(); }

        return $user;
    }

    public function consume(string $email, string $code, string $purpose = EmailOtpChallenge::PURPOSE_CLIENT_LOGIN): EmailOtpChallenge
    {
        $email = $this->normaliseEmail($email);

        $challenge = DB::transaction(function () use ($email, $code, $purpose): ?EmailOtpChallenge {
            $challenge = EmailOtpChallenge::query()->where('email', $email)->where('purpose', $purpose)->whereNull('consumed_at')->whereNull('invalidated_at')->latest('sent_at')->lockForUpdate()->first();
            if (! $challenge || $challenge->expires_at->isPast() || $challenge->attempts >= config('identity.otp.max_attempts')) {
                if ($challenge && ! $challenge->invalidated_at) { $challenge->forceFill(['invalidated_at' => now()])->save(); }
                return null;
            }
            if (! Hash::check($code, $challenge->code_hash)) {
                $challenge->increment('attempts');
                $challenge = $challenge->fresh();
                if ($challenge->attempts >= config('identity.otp.max_attempts')) { $challenge->forceFill(['invalidated_at' => now()])->save(); }
                return null;
            }

            $challenge->forceFill(['consumed_at' => now()])->save();
            return $challenge;
        });

        if (! $challenge) { throw new OtpVerificationFailed('Kod tidak sah atau telah tamat tempoh.'); }

        return $challenge;
    }

    private function normaliseEmail(string $email): string { return Str::lower(trim($email)); }
    private function rateKey(string $email, string $ipAddress, string $purpose = EmailOtpChallenge::PURPOSE_CLIENT_LOGIN): string { return ($purpose === EmailOtpChallenge::PURPOSE_CLIENT_LOGIN ? 'natnetwork:client-otp:' : 'natnetwork:'.strtolower($purpose).'-otp:').hash('sha256', $email.'|'.$ipAddress); }
}
