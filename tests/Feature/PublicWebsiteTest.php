<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PublicWebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render_the_seeded_catalog_and_actions(): void
    {
        $this->seed();

        $this->assertDatabaseCount('services', 6);
        $this->assertDatabaseCount('service_packages', 28);
        $this->assertDatabaseCount('addons', 90);
        $this->assertDatabaseCount('package_addons', 140);
        $this->get('/')->assertOk()->assertSee('Website Development')->assertSee('Mulakan projek')->assertSee('NatNetwork Synergy')->assertSee('FurniHome')->assertSee('images/home/background-section-1-edge.png')->assertSee('Hubungi');
        $this->get('/services')->assertOk()->assertSee('Custom Web Applications')->assertSee('RM1,500')->assertSee('Hero section')->assertSee('RM199 / bulan')->assertSee('Shared Add-on Catalogue')->assertSee('RM200–RM500/month')->assertSee('Home')->assertSee('Hubungi');
        $this->get('/pricing')->assertRedirect('/services')->assertStatus(301);
        $this->get('/peluang')->assertOk()->assertSee('Kongsi Peluang')->assertSee('Komisen Berdasarkan Nilai Jualan')->assertSee('Task Mingguan')->assertSee(route('affiliate.register'), false);
        $this->get('/contact')->assertOk()->assertSee('NATNETWORK SYNERGY')->assertSee('Buka alamat dalam peta')->assertSee('enquiry@natnetwork.net')->assertSee('011-1670 05857')->assertSee('Lihat sijil pendaftaran')->assertSee('Borang pertanyaan')->assertSee('natnetworksynergy');
        $this->get('/demo')->assertOk()->assertSee('Galeri Contoh')->assertSee('Promosi Service')->assertSee('GrowBiz.my')->assertSee('Rumah Rimba Homestay')->assertSee('/demo/web/landing-page/design1/');
        $this->get('/register')->assertOk()->assertSee('Daftar sebagai Client')->assertSee('Pendaftaran Partnership belum diaktifkan.')->assertSee('Log masuk Client');
    }

    public function test_services_show_every_package_without_changing_content(): void
    {
        $this->seed();

        foreach (['/services'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $text = mb_strtolower(html_entity_decode(strip_tags($html)));
            foreach (\App\Engines\Pricing\Models\ServicePackage::all() as $package) {
                $this->assertStringContainsString(e($package->name), $html, $url.' '.$package->slug);
                $this->assertStringContainsString(e($package->price_label), $html, $url.' '.$package->slug);
                $this->assertStringContainsString(e(route('builder.start', ['package' => $package->slug])), $html, $url.' '.$package->slug);
                if ($package->inclusions) {
                    foreach ($package->inclusions as $item) {
                        $this->assertStringContainsString(mb_strtolower($item), $text, $url.' '.$package->slug.' '.$item);
                    }
                } else {
                    foreach (preg_split('/[\s,;.]+/u', mb_strtolower((string) $package->summary), -1, PREG_SPLIT_NO_EMPTY) as $word) {
                        $this->assertStringContainsString($word, $text, $url.' '.$package->slug.' '.$word);
                    }
                }
                foreach ($package->packageAddons as $packageAddon) {
                    $this->assertStringContainsString('<li><span>'.e($packageAddon->displayName()).'</span><b>'.e($packageAddon->price_label).'</b></li>', $html, $url.' '.$package->slug.' '.$packageAddon->name);
                }
            }
            $this->assertStringNotContainsString('Popular', $html);
            foreach (\App\Engines\Pricing\Models\Addon::query()->whereNotNull('catalogue_order')->get() as $addon) {
                $this->assertStringContainsString(e($addon->price_label), $html, $addon->slug);
            }
            $this->assertStringContainsString('tidak termasuk dalam harga pembangunan', $html);
            \App\Engines\Pricing\Models\ServicePackage::query()->where('slug', 'business-integration')->update(['is_active' => false]);
            $this->get($url)->assertOk()->assertDontSee('Business Integration')->assertSee('Basic API Integration');
        }
    }

    public function test_demo_gallery_lists_real_demos_with_categories_search_and_exact_links(): void
    {
        $html = $this->get(route('gallery.examples'))->assertOk()->getContent();
        foreach (['Semua Kategori', 'Landing Page', 'Website Bisnes', 'E-Commerce', 'Hospitaliti', 'Pendidikan', 'Organisasi', 'Lain-lain', 'Cari contoh website...', 'Terkini', 'Tiada contoh ditemui'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
        foreach (['/demo/web/landing-page/design1/', '/demo/web/business/design1/', '/demo/web/starter/design1/'] as $path) {
            $this->assertStringContainsString('href="'.rtrim(url('/'), '/').$path.'"', $html);
        }
        $this->assertSame(3, substr_count($html, '<article class="dg-card"'));
        $this->assertStringNotContainsString('illustration', $html);
    }

    public function test_contact_form_sends_a_validated_enquiry(): void
    {
        Mail::fake();
        config(['mail.default' => 'array']);

        $this->post('/contact', [
            'name' => 'Rafez',
            'company' => 'NatNetwork Synergy',
            'email' => 'rafez@example.com',
            'phone' => '011-1670 05857',
            'subject' => 'Pertanyaan servis',
            'message' => 'Saya ingin mengetahui lebih lanjut tentang servis anda.',
        ])->assertRedirect(route('contact'))->assertSessionHas('status');
    }

    public function test_contact_and_builder_identity_submissions_are_throttled(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post('/contact', [])->assertStatus(302);
            $this->post(route('builder.identity'), [])->assertStatus(302);
        }

        $this->post('/contact', [])->assertStatus(429);
        $this->post(route('builder.identity'), [])->assertStatus(429);
    }

    public function test_demo_links_point_to_the_demo_gallery(): void
    {
        $this->seed();
        $demo = route('gallery.examples');

        $this->get('/')->assertOk()->assertSee('href="'.$demo.'">Lihat lebih banyak projek', false)->assertSee('href="'.$demo.'">Demo</a>', false);
        $this->get('/services')->assertOk()->assertSee('href="'.$demo.'">Demo</a>', false)->assertSee('href="'.$demo.'">Projek</a>', false);
        $this->get($demo)->assertOk()->assertSee('/demo/web/starter/design1/')->assertDontSee('gallery-contoh');
    }
}
