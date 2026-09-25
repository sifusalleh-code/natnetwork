<?php

namespace App\Engines\Affiliate\Services;

use App\Engines\Affiliate\Models\Affiliate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AffiliateProfileService
{
    public const AVATAR_DISK = 'local';

    public const STATES = ['Johor', 'Kedah', 'Kelantan', 'Melaka', 'Negeri Sembilan', 'Pahang', 'Perak', 'Perlis', 'Pulau Pinang', 'Sabah', 'Sarawak', 'Selangor', 'Terengganu', 'Wilayah Persekutuan Kuala Lumpur', 'Wilayah Persekutuan Labuan', 'Wilayah Persekutuan Putrajaya'];

    public const BANKS = ['Maybank', 'CIMB', 'Public Bank', 'RHB', 'Hong Leong Bank', 'AmBank', 'Bank Islam', 'Bank Muamalat', 'BSN', 'Bank Rakyat', 'Affin Bank', 'Alliance Bank', 'HSBC', 'OCBC', 'UOB', 'Standard Chartered'];

    /** @param array<string, mixed> $data */
    public function update(Affiliate $affiliate, array $data): Affiliate
    {
        return DB::transaction(function () use ($affiliate, $data): Affiliate {
            $affiliate = Affiliate::query()->lockForUpdate()->findOrFail($affiliate->id);

            if (! $affiliate->username && filled($data['username'] ?? null)) {
                $affiliate->username = Str::lower(trim($data['username']));
            }

            $affiliate->phone = $data['phone'];
            $affiliate->state = $data['state'];
            $affiliate->bank_name = $data['bank_name'];
            $affiliate->facebook_url = $data['facebook_url'] ?? null;
            $affiliate->instagram_url = $data['instagram_url'] ?? null;
            $affiliate->twitter_url = $data['twitter_url'] ?? null;

            $account = preg_replace('/\s+/', '', (string) ($data['bank_account_number'] ?? ''));
            if ($account !== '') {
                $affiliate->bank_account_number = $account;
                $affiliate->bank_account_last4 = substr($account, -4);
            }

            $affiliate->save();

            return $this->refreshCompletion($affiliate);
        });
    }

    public function replaceAvatar(Affiliate $affiliate, UploadedFile $file): Affiliate
    {
        $path = $file->storeAs('affiliates/avatars/'.$affiliate->id, Str::random(40).'.'.$file->extension(), self::AVATAR_DISK);
        $old = $affiliate->avatar_path;

        $affiliate->forceFill(['avatar_path' => $path])->save();

        if ($old && $old !== $path) {
            Storage::disk(self::AVATAR_DISK)->delete($old);
        }

        return $this->refreshCompletion($affiliate);
    }

    public function missingFields(Affiliate $affiliate): array
    {
        $labels = [
            'email_verified_at' => 'Pengesahan emel',
            'avatar_path' => 'Gambar profil',
            'username' => 'Username',
            'phone' => 'No. Telefon / WhatsApp',
            'state' => 'Negeri',
            'bank_name' => 'Bank',
            'bank_account_last4' => 'No. Akaun Bank',
        ];

        return array_values(array_filter($labels, fn (string $label, string $field): bool => blank($affiliate->{$field}), ARRAY_FILTER_USE_BOTH));
    }

    private function refreshCompletion(Affiliate $affiliate): Affiliate
    {
        $complete = $this->missingFields($affiliate) === [];

        if ($complete && ! $affiliate->profile_completed_at) {
            $affiliate->forceFill(['profile_completed_at' => now()])->save();
        } elseif (! $complete && $affiliate->profile_completed_at) {
            $affiliate->forceFill(['profile_completed_at' => null])->save();
        }

        return $affiliate;
    }
}
