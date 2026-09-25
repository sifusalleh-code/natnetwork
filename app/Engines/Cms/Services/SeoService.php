<?php

namespace App\Engines\Cms\Services;

use App\Engines\Pricing\Models\Service;
use App\Engines\Cms\Models\SeoPage;
use App\Engines\Cms\Models\SeoSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SeoService
{
    public const IMAGE_DIR = 'seo';
    public const OG_WIDTH = 1200;
    public const OG_HEIGHT = 630;

    private ?SeoSetting $settings = null;
    private ?Collection $pages = null;
    private ?object $request = null;

    /** Cache ringkas per request (elak query berulang dalam satu paparan). */
    private function fresh(): void
    {
        if ($this->request !== request()) {
            $this->forget();
            $this->request = request();
        }
    }

    public function settings(): SeoSetting
    {
        $this->fresh();

        return $this->settings ??= SeoSetting::currentOrDefault();
    }

    /** @return Collection<string, SeoPage> */
    public function overrides(): Collection
    {
        $this->fresh();

        return $this->pages ??= SeoPage::query()->get()->keyBy('route_name');
    }

    public function forget(): void
    {
        $this->settings = null;
        $this->pages = null;
    }

    public function isIndexable(string $route): bool
    {
        $config = config('seo.pages')[$route] ?? null;
        if (! $config) {
            return false;
        }
        $override = $this->overrides()->get($route)?->noindex;

        return $override === null ? (bool) $config['index'] : ! $override;
    }

    /** Metadata lengkap untuk <head>. */
    public function resolve(?string $route, ?string $viewTitle, array $context = []): array
    {
        $settings = $this->settings();
        $config = $route ? (config('seo.pages')[$route] ?? null) : null;
        $override = $route ? $this->overrides()->get($route) : null;
        $siteName = $settings->site_name ?: config('seo.site_name');

        $title = $override?->title ?: (($config['title'] ?? null) ?: ($viewTitle ?: $siteName));
        $description = $override?->description ?: ($config['description'] ?? null) ?: ($settings->default_description ?: config('seo.default_description'));
        $indexable = $route ? $this->isIndexable($route) : false;
        $canonical = $this->absolute('/'.ltrim(request()->path(), '/'));
        $image = $this->imageUrl($override?->og_image) ?? $this->imageUrl($settings->default_og_image) ?? $this->absolute(asset(config('seo.default_og_image')));

        return [
            'title' => $title,
            'description' => Str::limit(trim(preg_replace('/\s+/u', ' ', $description)), 300, ''),
            'robots' => $indexable ? 'index, follow, max-image-preview:large' : 'noindex, nofollow',
            'indexable' => $indexable,
            'canonical' => $canonical,
            'site_name' => $siteName,
            'image' => $image,
            'image_width' => self::OG_WIDTH,
            'image_height' => self::OG_HEIGHT,
            'locale' => config('seo.locale'),
            'theme_color' => config('seo.theme_color'),
            'google_verification' => $settings->google_site_verification,
            'bing_verification' => $settings->bing_site_verification,
            'json_ld' => $indexable ? $this->jsonLd($route, $title, $description, $canonical, $context) : null,
        ];
    }

    public function jsonLd(string $route, string $title, string $description, string $canonical, array $context = []): array
    {
        $home = $this->absolute('/');
        $orgId = $home.'#organization';
        $graph = [$this->organization($orgId, $home)];

        if ($route === 'home') {
            $graph[] = ['@type' => 'WebSite', '@id' => $home.'#website', 'url' => $home, 'name' => $this->settings()->site_name ?: config('seo.site_name'), 'inLanguage' => config('seo.language'), 'publisher' => ['@id' => $orgId]];
        }

        $page = ['@type' => 'WebPage', '@id' => $canonical.'#webpage', 'url' => $canonical, 'name' => $title, 'description' => $description, 'inLanguage' => config('seo.language'), 'isPartOf' => ['@id' => $home.'#website'], 'about' => ['@id' => $orgId]];
        if ($route !== 'home') {
            $page['breadcrumb'] = ['@type' => 'BreadcrumbList', 'itemListElement' => [
                ['@type' => 'ListItem', 'position' => 1, 'name' => config('seo.pages.home.label'), 'item' => $home],
                ['@type' => 'ListItem', 'position' => 2, 'name' => config('seo.pages')[$route]['label'] ?? $title, 'item' => $canonical],
            ]];
        }
        $graph[] = $page;

        $services = $context['services'] ?? null;
        if ($route === 'services' && $services instanceof Collection && $services->isNotEmpty()) {
            $graph[0]['hasOfferCatalog'] = $this->catalog($services, $orgId);
        }

        return ['@context' => 'https://schema.org', '@graph' => $graph];
    }

    private function organization(string $orgId, string $home): array
    {
        $company = config('company');
        $lines = $company['address_lines'] ?? [];
        $address = ['@type' => 'PostalAddress', 'addressCountry' => 'MY'];
        $last = array_pop($lines);
        if ($last && preg_match('/^(\d{5})\s+([^,]+),\s*(.+)$/u', $last, $m)) {
            $address += ['postalCode' => $m[1], 'addressLocality' => Str::title(mb_strtolower(trim($m[2]))), 'addressRegion' => Str::title(mb_strtolower(trim($m[3])))];
        } elseif ($last) {
            $lines[] = $last;
        }
        if ($lines) {
            $address['streetAddress'] = Str::title(mb_strtolower(implode(', ', $lines)));
        }

        return array_filter([
            '@type' => 'ProfessionalService',
            '@id' => $orgId,
            'name' => $this->settings()->site_name ?: config('seo.site_name'),
            'legalName' => $company['name'] ?? null,
            'url' => $home,
            'logo' => $this->absolute(asset('images/brand/natnetwork-synergy-primary.png')),
            'image' => $this->absolute(asset(config('seo.default_og_image'))),
            'email' => $company['email'] ?? null,
            'telephone' => $company['phone'] ?? null,
            'address' => $address,
            'sameAs' => array_values(array_filter([
                $company['socials']['facebook'] ?? null,
                $company['socials']['instagram'] ?? null,
                $company['socials']['linkedin'] ?? null,
            ])),
        ]);
    }

    private function catalog(Collection $services, string $orgId): array
    {
        $pricing = $this->absolute(route('services', [], false));

        return [
            '@type' => 'OfferCatalog',
            'name' => 'Servis & Harga',
            'itemListElement' => $services->map(fn (Service $service) => [
                '@type' => 'OfferCatalog',
                'name' => $service->name,
                'itemListElement' => $service->packages->map(function ($package) use ($orgId, $pricing, $service) {
                    $offer = [
                        '@type' => 'Offer',
                        'url' => $pricing.'#servis-'.$service->slug,
                        'itemOffered' => array_filter(['@type' => 'Service', 'name' => $package->name, 'description' => $package->summary, 'provider' => ['@id' => $orgId]]),
                    ];
                    $amount = $package->price_amount !== null ? number_format((float) $package->price_amount, 2, '.', '') : null;
                    if ($amount !== null && in_array($package->price_type, ['fixed', 'starting'], true)) {
                        $spec = ['@type' => str_contains((string) $package->price_label, 'bulan') ? 'UnitPriceSpecification' : 'PriceSpecification', 'priceCurrency' => 'MYR'];
                        $spec += $package->price_type === 'fixed' ? ['price' => $amount] : ['minPrice' => $amount];
                        if ($spec['@type'] === 'UnitPriceSpecification') {
                            $spec += ['unitCode' => 'MON', 'unitText' => 'bulan'];
                        }
                        $offer['priceSpecification'] = $spec;
                        if ($package->price_type === 'fixed') {
                            $offer += ['price' => $amount, 'priceCurrency' => 'MYR'];
                        }
                    }

                    return $offer;
                })->values()->all(),
            ])->values()->all(),
        ];
    }

    /** @return array<int, array{loc:string, lastmod:?string, changefreq:?string, priority:?string}> */
    public function sitemapEntries(): array
    {
        $catalogUpdated = Service::query()->max('updated_at');
        $entries = [];
        foreach (config('seo.pages') as $route => $page) {
            if (! $this->isIndexable($route) || ! \Illuminate\Support\Facades\Route::has($route)) {
                continue;
            }
            $dates = array_filter([$this->overrides()->get($route)?->updated_at, in_array($route, ['home', 'services'], true) ? $catalogUpdated : null]);
            $lastmod = $dates ? collect($dates)->map(fn ($d) => \Illuminate\Support\Carbon::parse($d))->max()->toAtomString() : null;
            $entries[] = ['loc' => $this->absolute(route($route, [], false)), 'lastmod' => $lastmod, 'changefreq' => $page['changefreq'] ?? null, 'priority' => $page['priority'] ?? null];
        }

        return $entries;
    }

    public function robotsTxt(): string
    {
        $lines = ['User-agent: *'];
        foreach (config('seo.robots.allow', []) as $path) {
            $lines[] = 'Allow: '.$path;
        }
        foreach (config('seo.robots.disallow', []) as $path) {
            $lines[] = 'Disallow: '.$path;
        }
        $lines[] = '';
        $lines[] = 'Sitemap: '.$this->absolute('/sitemap.xml');

        return implode("\n", $lines)."\n";
    }

    /** Simpan imej OG: dipotong (cover) kepada 1200x630 JPEG supaya saiz kecil dan paparan konsisten. */
    public function storeImage(UploadedFile $file): string
    {
        $source = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));
        if (! $source) {
            throw new \RuntimeException('Imej tidak dapat dibaca.');
        }
        [$sw, $sh] = [imagesx($source), imagesy($source)];
        $scale = max(self::OG_WIDTH / $sw, self::OG_HEIGHT / $sh);
        [$cw, $ch] = [(int) round(self::OG_WIDTH / $scale), (int) round(self::OG_HEIGHT / $scale)];
        $canvas = imagecreatetruecolor(self::OG_WIDTH, self::OG_HEIGHT);
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopyresampled($canvas, $source, 0, 0, (int) (($sw - $cw) / 2), (int) (($sh - $ch) / 2), self::OG_WIDTH, self::OG_HEIGHT, $cw, $ch);
        ob_start();
        imagejpeg($canvas, null, 85);
        $binary = (string) ob_get_clean();
        imagedestroy($canvas);
        imagedestroy($source);

        $name = sha1($binary).'.jpg';
        Storage::disk('local')->put(self::IMAGE_DIR.'/'.$name, $binary);

        return $name;
    }

    public function deleteImage(?string $name): void
    {
        if ($name && preg_match('/^[a-f0-9]{40}\.jpg$/', $name)) {
            $inUse = SeoPage::query()->where('og_image', $name)->count() + SeoSetting::query()->where('default_og_image', $name)->count();
            if ($inUse === 0) {
                Storage::disk('local')->delete(self::IMAGE_DIR.'/'.$name);
            }
        }
    }

    public function imageUrl(?string $name): ?string
    {
        return $name ? $this->absolute(route('seo.image', ['file' => $name], false)) : null;
    }

    /** URL mutlak berdasarkan APP_URL. */
    public function absolute(string $pathOrUrl): string
    {
        $base = rtrim((string) config('app.url'), '/');
        if (preg_match('#^https?://#i', $pathOrUrl)) {
            $parts = parse_url($pathOrUrl);
            $pathOrUrl = ($parts['path'] ?? '/').(isset($parts['query']) ? '?'.$parts['query'] : '');
        }

        return $base.'/'.ltrim($pathOrUrl, '/');
    }
}
