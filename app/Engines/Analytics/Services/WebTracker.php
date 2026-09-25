<?php

namespace App\Engines\Analytics\Services;

use App\Engines\Affiliate\Services\AffiliateTrackingService;
use App\Engines\Analytics\Models\WebEvent;
use App\Engines\Analytics\Models\WebPageView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use Throwable;

/**
 * Analytics Engine: rekod paparan halaman awam, ping (masa & pelawat live) dan klik CTA.
 * Tiada IP disimpan. Kegagalan penjejakan tidak pernah menjejaskan halaman.
 */
class WebTracker
{
    public const SESSION_COOKIE = 'nn_sid';

    private const BOT_PATTERN = '/bot|crawl|spider|slurp|facebookexternalhit|embedly|preview|monitor|pingdom|curl|wget|python|go-http|headless|lighthouse|scrapy|httpclient|java\//i';

    public function shouldTrack(Request $request): bool
    {
        if (! $request->isMethod('GET') || $request->expectsJson() || $request->ajax()) {
            return false;
        }
        $route = $request->route()?->getName();
        if (! $route || ! array_key_exists($route, config('analytics.pages'))) {
            return false;
        }
        if (auth('admin')->check()) {
            return false; // paparan admin tidak dikira
        }
        $agent = (string) $request->userAgent();

        return $agent !== '' && ! preg_match(self::BOT_PATTERN, $agent) && $request->header('Purpose') !== 'prefetch' && $request->header('Sec-Purpose') === null;
    }

    public function record(Request $request, string $publicId): void
    {
        try {
            $visitor = $this->queuedOrCookie($request, AffiliateTrackingService::VISITOR_COOKIE);
            $isNewVisitor = $visitor === null;
            $visitor ??= (string) Str::uuid();
            $session = $this->queuedOrCookie($request, self::SESSION_COOKIE);
            $isEntry = $session === null;
            $session ??= (string) Str::uuid();

            $source = $host = null;
            if ($isEntry) {
                [$source, $host] = $this->classifySource($request);
            }
            $agent = (string) $request->userAgent();
            $country = strtoupper((string) $request->header('CF-IPCountry'));

            WebPageView::query()->create([
                'public_id' => $publicId,
                'visitor_token' => $visitor,
                'session_token' => $session,
                'route_name' => $request->route()?->getName(),
                'path' => Str::limit('/'.ltrim($request->path(), '/'), 490, ''),
                'is_entry' => $isEntry,
                'is_new_visitor' => $isNewVisitor,
                'source' => $source,
                'source_host' => $host,
                'referrer' => $isEntry ? (Str::limit((string) $request->headers->get('referer'), 490, '') ?: null) : null,
                'utm_source' => $this->utm($request, 'utm_source'),
                'utm_medium' => $this->utm($request, 'utm_medium'),
                'utm_campaign' => $this->utm($request, 'utm_campaign'),
                'device_type' => self::device($agent),
                'browser' => self::browser($agent),
                'country' => preg_match('/^[A-Z]{2}$/', $country) && ! in_array($country, ['XX', 'T1'], true) ? $country : null,
                'viewed_at' => now(),
                'last_seen_at' => now(),
            ]);

            Cookie::queue(AffiliateTrackingService::VISITOR_COOKIE, $visitor, 60 * 24 * 365 * 2);
            Cookie::queue(self::SESSION_COOKIE, $session, (int) config('analytics.session_minutes'));
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** Ping daripada halaman yang masih dibuka: kemas kini masa (dikira di pelayan) dan status live. */
    public function ping(Request $request, string $publicId): bool
    {
        $view = $this->ownedView($request, $publicId);
        if (! $view) {
            return false;
        }
        $seconds = min((int) config('analytics.max_duration_seconds'), max(0, (int) $view->viewed_at->diffInSeconds(now())));
        $view->forceFill(['last_seen_at' => now(), 'duration_seconds' => $seconds])->save();
        Cookie::queue(self::SESSION_COOKIE, $view->session_token, (int) config('analytics.session_minutes'));

        return true;
    }

    public function event(Request $request, string $publicId, string $cta): bool
    {
        $view = $this->ownedView($request, $publicId);
        if (! $view || ! array_key_exists($cta, config('analytics.ctas'))) {
            return false;
        }
        WebEvent::query()->create([
            'web_page_view_id' => $view->id, 'visitor_token' => $view->visitor_token, 'session_token' => $view->session_token,
            'name' => 'cta_click', 'label' => $cta, 'path' => $view->path, 'occurred_at' => now(),
        ]);

        return true;
    }

    /** @return array{0: string, 1: ?string} */
    public function classifySource(Request $request): array
    {
        $referrer = (string) $request->headers->get('referer');
        $host = $referrer !== '' ? Str::lower((string) parse_url($referrer, PHP_URL_HOST)) : '';
        $host = preg_replace('/^(www\.|m\.|l\.|lm\.)/', '', $host);
        $ownHost = Str::lower((string) parse_url((string) config('app.url'), PHP_URL_HOST));
        $isInternal = $host !== '' && ($host === $request->getHost() || $host === $ownHost);

        if (is_string($request->query('ref')) && $request->query('ref') !== '') {
            return ['affiliate', $isInternal ? null : ($host ?: null)];
        }
        if ($this->utm($request, 'utm_source') || $this->utm($request, 'utm_campaign')) {
            return ['campaign', $this->utm($request, 'utm_source')];
        }
        if ($host === '' || $isInternal) {
            return ['direct', null];
        }
        foreach (config('analytics.search_hosts') as $needle) {
            if (str_contains($host, $needle)) {
                return ['organic', $host];
            }
        }
        foreach (config('analytics.social_hosts') as $social) {
            if ($host === $social || str_ends_with($host, '.'.$social)) {
                return ['social', $host];
            }
        }

        return ['referral', $host];
    }

    public static function device(string $agent): string
    {
        if (preg_match('/ipad|tablet|playbook|silk|(android(?!.*mobile))/i', $agent)) {
            return 'tablet';
        }

        return preg_match('/mobi|iphone|ipod|android|blackberry|opera mini|iemobile/i', $agent) ? 'mobile' : 'desktop';
    }

    public static function browser(string $agent): string
    {
        return match (true) {
            (bool) preg_match('/Edg(e|A|iOS)?\//', $agent) => 'Edge',
            (bool) preg_match('/OPR\/|Opera/', $agent) => 'Opera',
            (bool) preg_match('/SamsungBrowser/', $agent) => 'Samsung Internet',
            (bool) preg_match('/Firefox\/|FxiOS/', $agent) => 'Firefox',
            (bool) preg_match('/Chrome\/|CriOS/', $agent) => 'Chrome',
            (bool) preg_match('/Safari\//', $agent) => 'Safari',
            default => 'Lain-lain',
        };
    }

    private function ownedView(Request $request, string $publicId): ?WebPageView
    {
        $visitor = $request->cookie(AffiliateTrackingService::VISITOR_COOKIE);
        if (! Str::isUuid($publicId) || ! is_string($visitor) || ! Str::isUuid($visitor)) {
            return null;
        }

        return WebPageView::query()->where('public_id', $publicId)->where('visitor_token', $visitor)
            ->where('viewed_at', '>=', now()->subHours(6))->first();
    }

    private function queuedOrCookie(Request $request, string $name): ?string
    {
        $value = $request->cookie($name);
        if (! is_string($value) || ! Str::isUuid($value)) {
            // Klik affiliate dalam permintaan yang sama baru mencipta cookie pelawat: guna nilai itu supaya kedua-dua rekod sepadan.
            $value = $name === AffiliateTrackingService::VISITOR_COOKIE && $request->query('ref') !== null ? Cookie::queued($name)?->getValue() : null;
        }

        return is_string($value) && Str::isUuid($value) ? $value : null;
    }

    private function utm(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) && trim($value) !== '' ? Str::limit(Str::lower(trim($value)), 110, '') : null;
    }
}
