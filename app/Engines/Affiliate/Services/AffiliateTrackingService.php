<?php

namespace App\Engines\Affiliate\Services;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliateClick;
use App\Engines\Affiliate\Models\AffiliateClientLink;
use App\Engines\Affiliate\Models\AffiliateSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class AffiliateTrackingService
{
    public const REFERRAL_COOKIE = 'nn_aff';
    public const VISITOR_COOKIE = 'nn_vid';
    public const SOURCES = ['facebook', 'instagram', 'whatsapp', 'tiktok', 'telegram', 'x', 'linkedin', 'direct', 'other'];

    private const REFERRER_HOSTS = [
        'facebook' => ['facebook.com', 'fb.com', 'fb.me'],
        'instagram' => ['instagram.com'],
        'whatsapp' => ['whatsapp.com', 'wa.me'],
        'tiktok' => ['tiktok.com'],
        'telegram' => ['telegram.org', 'telegram.me', 't.me'],
        'x' => ['x.com', 'twitter.com', 't.co'],
        'linkedin' => ['linkedin.com', 'lnkd.in'],
    ];

    /**
     * Rekod klik link affiliate (?ref=username) dan simpan cookie. Klik terbaru menimpa cookie lama.
     */
    public function recordClick(Request $request, string $username): ?AffiliateClick
    {
        $affiliate = Affiliate::query()
            ->where('username', Str::lower(trim($username)))
            ->where('status', Affiliate::STATUS_ACTIVE)
            ->whereNotNull('profile_completed_at')
            ->first();

        if (! $affiliate) {
            return null;
        }

        $visitor = $request->cookie(self::VISITOR_COOKIE);
        if (! is_string($visitor) || ! Str::isUuid($visitor)) {
            $visitor = (string) Str::uuid();
        }

        $click = AffiliateClick::query()->create([
            'affiliate_id' => $affiliate->id,
            'visitor_token' => $visitor,
            'source' => $this->detectSource($request),
            'device_type' => $this->detectDevice((string) $request->userAgent()),
            'referrer' => Str::limit((string) $request->headers->get('referer'), 490, '') ?: null,
            'landing_path' => Str::limit('/'.ltrim($request->path(), '/'), 490, ''),
            'ip_hash' => $request->ip() ? hash('sha256', $request->ip().'|'.config('app.key')) : null,
            'user_agent' => Str::limit((string) $request->userAgent(), 490, '') ?: null,
            'clicked_at' => now(),
        ]);

        $minutes = AffiliateSetting::cookieDays() * 24 * 60;
        Cookie::queue(self::REFERRAL_COOKIE, (string) $click->id, $minutes);
        Cookie::queue(self::VISITOR_COOKIE, $visitor, 60 * 24 * 365 * 2);

        return $click;
    }

    /**
     * Kaitkan pelanggan BARU (akaun pertama kali dicipta + emel disahkan) dengan affiliate daripada cookie yang sah.
     */
    public function linkNewCustomer(User $customer, Request $request): ?AffiliateClientLink
    {
        $clickId = $request->cookie(self::REFERRAL_COOKIE);
        if (! is_string($clickId) || ! ctype_digit($clickId)) {
            return null;
        }

        $click = AffiliateClick::query()
            ->whereKey((int) $clickId)
            ->where('clicked_at', '>=', now()->subDays(AffiliateSetting::cookieDays()))
            ->first();

        if (! $click || AffiliateClientLink::query()->where('customer_user_id', $customer->id)->exists()) {
            return null;
        }

        $link = AffiliateClientLink::query()->create([
            'affiliate_id' => $click->affiliate_id,
            'customer_user_id' => $customer->id,
            'affiliate_click_id' => $click->id,
            'linked_at' => now(),
        ]);

        Cookie::queue(Cookie::forget(self::REFERRAL_COOKIE));

        return $link;
    }

    public function detectSource(Request $request): string
    {
        $src = Str::lower((string) $request->query('src', ''));
        if (in_array($src, self::SOURCES, true)) {
            return $src;
        }

        $host = Str::lower((string) parse_url((string) $request->headers->get('referer'), PHP_URL_HOST));
        if ($host === '') {
            return 'direct';
        }

        foreach (self::REFERRER_HOSTS as $source => $domains) {
            foreach ($domains as $domain) {
                if ($host === $domain || Str::endsWith($host, '.'.$domain)) {
                    return $source;
                }
            }
        }

        return 'other';
    }

    public function detectDevice(string $userAgent): string
    {
        return preg_match('/Mobile|Android|iPhone|iPad|iPod|Opera Mini|IEMobile/i', $userAgent) ? 'mobile' : 'desktop';
    }
}
