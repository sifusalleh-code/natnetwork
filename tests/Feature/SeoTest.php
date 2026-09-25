<?php

namespace Tests\Feature;

use App\Engines\Cms\Models\SeoPage;
use App\Engines\Identity\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_have_unique_meta_canonical_open_graph_and_json_ld(): void
    {
        $this->seed();
        $base = rtrim(config('app.url'), '/');
        $titles = [];

        foreach (['/' => 'home', '/services' => 'services', '/peluang' => 'opportunity', '/contact' => 'contact', '/demo' => 'gallery.examples'] as $url => $route) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('<meta name="robots" content="index, follow, max-image-preview:large">', $html, $url);
            $this->assertStringContainsString('<link rel="canonical" href="'.$base.($url === '/' ? '/' : $url).'">', $html, $url);
            $this->assertStringContainsString('<meta property="og:image" content="'.$base.'/images/seo/og-default.png">', $html, $url);
            $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $html, $url);
            $this->assertStringContainsString('<meta name="description" content="'.e(config('seo.pages')[$route]['description']).'">', $html, $url);
            preg_match('#<title>(.*?)</title>#', $html, $m);
            $titles[] = $m[1];
            preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $html, $ld);
            $json = json_decode($ld[1] ?? '', true);
            $this->assertIsArray($json, $url);
            $this->assertSame('ProfessionalService', $json['@graph'][0]['@type']);
            $this->assertSame('72500', $json['@graph'][0]['address']['postalCode']);
        }
        $this->assertCount(5, array_unique($titles));

        $pricing = $this->get('/services')->getContent();
        preg_match('#<script type="application/ld\+json">(.*?)</script>#s', $pricing, $ld);
        $catalog = json_decode($ld[1], true)['@graph'][0]['hasOfferCatalog'];
        $this->assertCount(6, $catalog['itemListElement']);
        $landing = collect($catalog['itemListElement'][0]['itemListElement'])->firstWhere('itemOffered.name', 'Landing Page');
        $this->assertSame('1500.00', $landing['price']);
        $this->assertSame('MYR', $landing['priceCurrency']);
    }

    public function test_private_and_auth_pages_are_noindex_without_json_ld(): void
    {
        $this->seed();
        foreach (['/client/login', '/affiliate/login', '/partner/login', '/register', '/admin/login'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringContainsString('<meta name="robots" content="noindex, nofollow">', $html, $url);
            $this->assertStringNotContainsString('rel="canonical"', $html, $url);
            $this->assertStringNotContainsString('application/ld+json', $html, $url);
        }
    }

    public function test_sitemap_lists_only_indexable_pages_and_robots_file_is_generated(): void
    {
        $this->seed();
        $base = rtrim(config('app.url'), '/');
        $xml = $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8')->getContent();
        $this->assertNotFalse(simplexml_load_string($xml));
        $this->assertStringContainsString('<loc>'.$base.'/services</loc>', $xml);
        $this->assertStringContainsString('<loc>'.$base.'/</loc>', $xml);
        $this->assertStringNotContainsString('/register</loc>', str_replace(['/affiliate/register', '/partner/register'], '', $xml));
        $this->assertStringNotContainsString('/admin', $xml);

        SeoPage::query()->create(['route_name' => 'contact', 'noindex' => true]);
        $this->assertStringNotContainsString('/contact</loc>', $this->get('/sitemap.xml')->getContent());
        $this->assertStringContainsString('noindex, nofollow', $this->get('/contact')->getContent());

        $robots = public_path('robots.txt');
        $backup = is_file($robots) ? file_get_contents($robots) : null;
        try {
            $this->artisan('seo:robots')->assertSuccessful();
            $txt = file_get_contents($robots);
            $this->assertStringContainsString('Disallow: /admin', $txt);
            $this->assertStringContainsString('Sitemap: '.$base.'/sitemap.xml', $txt);
        } finally {
            $backup === null ? @unlink($robots) : file_put_contents($robots, $backup);
        }
    }

    public function test_admin_can_override_page_seo_and_upload_share_image(): void
    {
        $this->seed();
        Storage::fake('local');
        $admin = Admin::query()->create(['name' => 'Nat', 'email' => 'admin@example.test', 'password' => 'password-yang-panjang']);
        $admin->forceFill(['two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'two_factor_confirmed_at' => now()])->save();

        $this->get('/admin/settings/seo')->assertRedirect();
        $this->actingAs($admin, 'admin')->get('/admin/settings/seo')->assertOk()->assertSee('Tetapan SEO')->assertSee('Peluang');

        $this->actingAs($admin, 'admin')->put('/admin/settings/seo/pages/services', [
            'title' => 'Harga Website Malaysia | NatNetwork',
            'description' => 'Huraian khas halaman harga.',
            'index_mode' => 'default',
            'og_image' => UploadedFile::fake()->image('share.png', 1600, 900),
        ])->assertRedirect();

        $page = SeoPage::query()->where('route_name', 'services')->firstOrFail();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{40}\.jpg$/', $page->og_image);
        Storage::disk('local')->assertExists('seo/'.$page->og_image);
        [$w, $h] = getimagesizefromstring(Storage::disk('local')->get('seo/'.$page->og_image));
        $this->assertSame([1200, 630], [$w, $h]);

        $html = $this->get('/services')->getContent();
        $this->assertStringContainsString('<title>Harga Website Malaysia | NatNetwork</title>', $html);
        $this->assertStringContainsString('content="Huraian khas halaman harga."', $html);
        $this->assertStringContainsString('/seo/image/'.$page->og_image, $html);
        $this->get('/seo/image/'.$page->og_image)->assertOk();
        $this->get('/seo/image/'.str_repeat('0', 40).'.jpg')->assertNotFound();

        $this->actingAs($admin, 'admin')->put('/admin/settings/seo/pages/admin.dashboard', ['index_mode' => 'index'])->assertNotFound();
        $this->actingAs($admin, 'admin')->put('/admin/settings/seo/pages/services', ['index_mode' => 'x'])->assertSessionHasErrors('index_mode');
        $this->actingAs($admin, 'admin')->put('/admin/settings/seo', ['site_name' => 'NatNetwork Synergy', 'google_site_verification' => '<meta name="x">'])->assertSessionHasErrors('google_site_verification');
        $this->actingAs($admin, 'admin')->put('/admin/settings/seo', ['site_name' => 'NatNetwork Synergy', 'google_site_verification' => 'abc_DEF-123'])->assertSessionHasNoErrors();
        $this->assertStringContainsString('<meta name="google-site-verification" content="abc_DEF-123">', $this->get('/')->getContent());
        $this->assertDatabaseHas('audit_logs', ['action' => 'SEO_PAGE_CHANGED']);
    }
}
