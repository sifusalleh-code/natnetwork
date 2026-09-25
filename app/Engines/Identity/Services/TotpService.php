<?php

namespace App\Engines\Identity\Services;

use App\Engines\Identity\Models\Admin;
use Illuminate\Support\Facades\DB;

/**
 * TOTP (RFC 6238, SHA1, 6 digit, 30 saat) untuk 2FA admin. Serasi dengan Google Authenticator / Microsoft Authenticator.
 */
class TotpService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const WINDOW = 1;

    public function generateSecret(): string
    {
        $bytes = random_bytes(20);
        $bits = '';
        foreach (str_split($bytes) as $byte) {
            $bits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $secret = '';
        foreach (str_split($bits, 5) as $chunk) {
            $secret .= self::ALPHABET[bindec(str_pad($chunk, 5, '0'))];
        }

        return $secret;
    }

    public function otpauthUri(string $secret, string $email): string
    {
        $issuer = rawurlencode(config('app.name', 'NatNetwork').' Admin');

        return 'otpauth://totp/'.$issuer.':'.rawurlencode($email).'?secret='.$secret.'&issuer='.$issuer.'&digits='.self::DIGITS.'&period='.self::PERIOD;
    }

    public function codeAt(string $secret, int $step): string
    {
        $hash = hash_hmac('sha1', pack('N*', 0).pack('N*', $step), $this->base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $value = ((ord($hash[$offset]) & 0x7F) << 24) | ((ord($hash[$offset + 1]) & 0xFF) << 16) | ((ord($hash[$offset + 2]) & 0xFF) << 8) | (ord($hash[$offset + 3]) & 0xFF);

        return str_pad((string) ($value % (10 ** self::DIGITS)), self::DIGITS, '0', STR_PAD_LEFT);
    }

    public function currentStep(?int $timestamp = null): int
    {
        return intdiv($timestamp ?? time(), self::PERIOD);
    }

    /** Pulangkan step yang sepadan, atau null. */
    public function matchStep(string $secret, string $code, ?int $timestamp = null): ?int
    {
        if (! preg_match('/^\d{6}$/', $code)) {
            return null;
        }

        $step = $this->currentStep($timestamp);
        for ($i = -self::WINDOW; $i <= self::WINDOW; $i++) {
            if (hash_equals($this->codeAt($secret, $step + $i), $code)) {
                return $step + $i;
            }
        }

        return null;
    }

    /** Sahkan kod untuk admin dan halang kod yang sama digunakan semula (replay). */
    public function verifyForAdmin(Admin $admin, string $code, ?string $secret = null): bool
    {
        return DB::transaction(function () use ($admin, $code, $secret): bool {
            $locked = Admin::query()->lockForUpdate()->findOrFail($admin->id);
            $step = $this->matchStep($secret ?? (string) $locked->two_factor_secret, $code);

            if ($step === null || ($locked->two_factor_last_step !== null && $step <= $locked->two_factor_last_step)) {
                return false;
            }

            $locked->forceFill(['two_factor_last_step' => $step])->save();

            return true;
        });
    }

    private function base32Decode(string $secret): string
    {
        $bits = '';
        foreach (str_split(strtoupper(rtrim($secret, '='))) as $char) {
            $index = strpos(self::ALPHABET, $char);
            if ($index === false) {
                continue;
            }
            $bits .= str_pad(decbin($index), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($bits, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }

        return $bytes;
    }
}
