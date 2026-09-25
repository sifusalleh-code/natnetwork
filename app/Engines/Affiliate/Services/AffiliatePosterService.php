<?php

namespace App\Engines\Affiliate\Services;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliatePoster;
use App\Engines\Affiliate\Models\AffiliateShare;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AffiliatePosterService
{
    public const DIR = 'affiliate-posters';
    public const MIMES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /** @return array{path: string, mime: string} */
    public function storeImage(UploadedFile $file): array
    {
        $mime = (string) $file->getMimeType();
        abort_unless(isset(self::MIMES[$mime]), 422, 'Jenis imej tidak dibenarkan.');
        $path = $file->storeAs(self::DIR, Str::random(40).'.'.self::MIMES[$mime], 'local');

        return ['path' => $path, 'mime' => $mime];
    }

    public function deleteImage(?string $path): void
    {
        if ($path && str_starts_with($path, self::DIR.'/')) {
            Storage::disk('local')->delete($path);
        }
    }

    public function referralLink(Affiliate $affiliate): string
    {
        return rtrim((string) config('app.url'), '/').'/?ref='.$affiliate->username;
    }

    public function recordShare(Affiliate $affiliate, AffiliatePoster $poster, string $channel): AffiliateShare
    {
        return AffiliateShare::query()->create(['affiliate_id' => $affiliate->id, 'affiliate_poster_id' => $poster->id, 'channel' => $channel, 'shared_at' => now()]);
    }

    /** URL niat perkongsian (dibuka dalam tab baharu). */
    public function intentUrl(string $channel, string $link, string $caption): ?string
    {
        return match ($channel) {
            'whatsapp' => 'https://wa.me/?text='.rawurlencode($caption),
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($link),
            'telegram' => 'https://t.me/share/url?url='.rawurlencode($link).'&text='.rawurlencode(trim(str_replace($link, '', $caption))),
            default => null,
        };
    }
}
