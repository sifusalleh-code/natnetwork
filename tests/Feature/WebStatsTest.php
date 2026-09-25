<?php

namespace Tests\Feature;

use App\Engines\Analytics\Models\WebEvent;
use App\Engines\Analytics\Models\WebPageView;
use App\Engines\Identity\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebStatsTest extends TestCase
{
    use RefreshDatabase;

    private const UA_MOBILE = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1';
    private const UA_DESKTOP = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36';

    public function test_public_page_views_are_recorded_with_source_device_and_country(): void
    {
        $this->seed();
        $this->withHeaders(['User-Agent' => self::UA_MOBILE, 'Referer' => 'https://www.google.com/', 'CF-IPCountry' => 'MY'])->get('/')->assertOk()->assertSee('_nn', false);

        $view = WebPageView::query()->firstOrFail();
        $this->assertSame(['home', true, true, 'organic', 'google.com', 'mobile', 'Safari', 'MY'], [$view->route_name, $view->is_entry, $view->is_new_visitor, $view->source, $view->source_host, $view->device_type, $view->browser, $view->country]);

        // Paparan kedua dalam sesi yang sama: bukan halaman masuk, bukan pelawat baharu.
        $this->withCookies(['nn_vid' => $view->visitor_token, 'nn_sid' => $view->session_token])->withHeaders(['User-Agent' => self::UA_MOBILE])->get('/services')->assertOk();
        $second = WebPageView::query()->latest('id')->firstOrFail();
        $this->assertFalse($second->is_entry);
        $this->assertFalse($second->is_new_visitor);
        $this->assertSame($view->session_token, $second->session_token);
    }

    public function test_bots_admins_portals_and_non_html_are_not_recorded(): void
    {
        $this->seed();
        $this->withHeaders(['User-Agent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)'])->get('/')->assertOk();
        $this->withHeaders(['User-Agent' => self::UA_DESKTOP])->get('/sitemap.xml')->assertOk();
        $this->withHeaders(['User-Agent' => self::UA_DESKTOP])->get('/admin/login')->assertOk();
        $this->actingAs($this->admin(), 'admin')->withHeaders(['User-Agent' => self::UA_DESKTOP])->get('/')->assertOk();
        $this->assertDatabaseCount('web_page_views', 0);
    }

    public function test_ping_and_cta_events_only_accept_the_visitors_own_page_view(): void
    {
        $this->seed();
        $this->withHeaders(['User-Agent' => self::UA_DESKTOP])->get('/?ref=nosuchuser&utm_source=fb')->assertOk();
        $view = WebPageView::query()->firstOrFail();
        $this->assertSame('affiliate', $view->source);

        $this->withCookies(['nn_vid' => $view->visitor_token])->post(route('analytics.event'), ['pv' => $view->public_id, 'cta' => 'mulakan-projek'])->assertNoContent();
        $this->withCookies(['nn_vid' => $view->visitor_token])->post(route('analytics.event'), ['pv' => $view->public_id, 'cta' => 'bukan-cta'])->assertNoContent();
        $this->withCookies(['nn_vid' => '00000000-0000-4000-8000-000000000000'])->post(route('analytics.event'), ['pv' => $view->public_id, 'cta' => 'hubungi'])->assertNoContent();
        $this->assertSame(['mulakan-projek'], WebEvent::query()->pluck('label')->all());

        $this->travel(95)->seconds();
        $this->withCookies(['nn_vid' => $view->visitor_token])->post(route('analytics.ping'), ['pv' => $view->public_id])->assertNoContent();
        $this->assertGreaterThanOrEqual(95, $view->fresh()->duration_seconds);
    }

    public function test_admin_stats_page_shows_real_figures_live_and_export(): void
    {
        $this->seed();
        foreach ([[self::UA_MOBILE, 'https://www.facebook.com/'], [self::UA_DESKTOP, null], [self::UA_DESKTOP, 'https://www.google.com/search']] as [$ua, $ref]) {
            $headers = array_filter(['User-Agent' => $ua, 'Referer' => $ref]);
            $this->flushCookies();
            $this->withHeaders($headers)->get('/')->assertOk();
        }
        $admin = $this->admin();

        $this->get(route('admin.web-stats'))->assertRedirect();
        $html = $this->actingAs($admin, 'admin')->get(route('admin.web-stats', ['range' => '7']))->assertOk()
            ->assertSee('Statistik Web')->assertSee('Traffic Overview')->assertSee('Sumber Trafik')->assertSee('Pengunjung Semasa')
            ->assertSee('Halaman Popular')->assertSee('Landing Page Performance')->assertSee('Conversion Funnel')->assertSee('Prestasi CTA')
            ->assertSee('Trafik Affiliate')->assertSee('Lokasi Pengunjung')->assertSee('Peranti &amp; Browser', false)->getContent();
        $this->assertStringContainsString('Laman Utama', $html);

        $this->actingAs($admin, 'admin')->getJson(route('admin.web-stats.live'))->assertOk()->assertJson(['count' => 3]);
        $this->actingAs($admin, 'admin')->get(route('admin.web-stats', ['range' => 'custom', 'from' => '2026-01-10', 'to' => '2026-01-01']))->assertOk();
        $csv = $this->actingAs($admin, 'admin')->get(route('admin.web-stats.export', ['range' => '30']))->assertOk()->streamedContent();
        $this->assertStringContainsString('"Laman Utama",3,3', $csv);
    }

    public function test_sidebar_links_to_web_stats_on_other_admin_pages(): void
    {
        $this->actingAs($this->admin(), 'admin')->get(route('admin.dashboard'))->assertOk()->assertSee(route('admin.web-stats').'#halaman', false)->assertSee('Log keluar');
    }

    private function flushCookies(): void
    {
        $this->defaultCookies = [];
        $this->unencryptedCookies = [];
    }

    private function admin(): Admin
    {
        $admin = Admin::query()->create(['name' => 'Nat', 'email' => 'admin@example.test', 'password' => 'password-yang-panjang']);
        $admin->forceFill(['two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'two_factor_confirmed_at' => now()])->save();

        return $admin;
    }
}
