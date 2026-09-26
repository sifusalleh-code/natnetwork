<?php

namespace Tests\Feature;

use App\Engines\Identity\Models\EmailOtpChallenge;
use App\Engines\Pricing\Models\ServicePackage;
use App\Engines\Sales\Models\BuilderSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectBuilderFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guided_builder_saves_valid_structured_answers(): void
    {
        $this->seed();
        $this->verifiedClient();
        $this->post(route('builder.entry'), ['entry_path' => 'GUIDED'])->assertRedirect(route('builder.start'));

        $package = ServicePackage::query()->where('slug', 'e-commerce-starter')->firstOrFail();
        $this->post(route('builder.save'), ['service_package_id' => $package->id, 'answers' => [
            'project_type' => 'e-commerce',
            'content_logo' => 'available',
        ]])->assertRedirect(route('builder.start'));

        $session = BuilderSession::query()->firstOrFail();
        $this->assertSame('GUIDED', $session->entry_path);
        $this->assertSame(['e-commerce'], $session->answers()->whereHas('question', fn ($query) => $query->where('code', 'project_type'))->firstOrFail()->value);
    }

    public function test_a_public_package_link_preselects_the_direct_builder_path(): void
    {
        $this->seed();
        $package = ServicePackage::query()->where('slug', 'landing-page')->firstOrFail();

        // Spec/inclusions pakej dipaparkan terus (sama seperti halaman Services), tanpa perlu ditick,
        // beserta kesesuaian penggunaan ("Sesuai untuk").
        $this->get(route('builder.start', ['package' => $package->slug]))->assertOk()->assertSee('Pakej pilihan anda')->assertSee('Landing Page')->assertSee('Hero section')->assertSee('Basic on-page SEO')->assertSee('Sesuai untuk')->assertSee('Kempen, promosi', false);
        $this->post(route('builder.entry'), ['entry_path' => 'DIRECT_SELECTION', 'package' => $package->slug])->assertRedirect(route('builder.start'));

        // Pakej sudah disahkan terus daripada Services: langkah "Model" dilangkau, terus ke "Gaya" (index 1).
        $this->assertDatabaseHas('builder_sessions', ['entry_path' => 'DIRECT_SELECTION', 'service_package_id' => $package->id, 'current_step' => 1]);
    }

    public function test_direct_selection_without_preset_package_still_starts_at_model_step(): void
    {
        $this->seed();

        // "Saya Dah Tahu" tanpa pakej pra-pilih (tiada ?package= dari Services): pelanggan masih
        // perlu pilih pakej sendiri dalam wizard, jadi langkah "Model" TIDAK dilangkau.
        $this->post(route('builder.entry'), ['entry_path' => 'DIRECT_SELECTION'])->assertRedirect(route('builder.start'));

        $session = \App\Engines\Sales\Models\BuilderSession::query()->firstOrFail();
        $this->assertSame('DIRECT_SELECTION', $session->entry_path);
        $this->assertNull($session->current_step);
    }

    public function test_viewing_the_builder_does_not_create_a_session_record(): void
    {
        $this->seed();

        $this->get(route('builder.start'))->assertOk();
        $this->assertDatabaseCount('builder_sessions', 0);

        $this->post(route('builder.entry'), ['entry_path' => 'GUIDED'])->assertRedirect(route('builder.start'));
        $this->assertDatabaseCount('builder_sessions', 1);
        $this->get(route('builder.start'))->assertOk();
        $this->assertDatabaseCount('builder_sessions', 1);
    }

    public function test_builder_rejects_an_unknown_configured_option(): void
    {
        $this->seed();
        $this->verifiedClient();
        $this->post(route('builder.entry'), ['entry_path' => 'GUIDED']);

        $this->post(route('builder.save'), ['answers' => ['project_type' => 'not-a-project-type'], 'service_package_id' => ServicePackage::query()->value('id')])
            ->assertSessionHasErrors('answers.project_type');
        $this->post(route('builder.save'), ['answers' => ['project_type' => 'website-development']])->assertSessionHasErrors('service_package_id');
    }

    public function test_builder_verification_creates_or_links_the_customer_account(): void
    {
        $this->seed();
        $builder = BuilderSession::query()->create([
            'entry_path' => 'GUIDED', 'contact_name' => 'Aminah', 'contact_company' => 'Aminah Co',
            'contact_email' => 'aminah@example.test', 'contact_phone' => '0123456789',
        ]);
        EmailOtpChallenge::query()->create([
            'email' => $builder->contact_email, 'purpose' => EmailOtpChallenge::PURPOSE_CLIENT_LOGIN,
            'code_hash' => Hash::make('123456'), 'attempts' => 0, 'sent_at' => now(), 'expires_at' => now()->addMinutes(10),
        ]);

        $this->withSession(['natnetwork_builder_session_id' => $builder->id])
            ->post(route('builder.verify'), ['code' => '123456'])
            ->assertRedirect(route('builder.start'));

        $user = User::query()->where('email', $builder->contact_email)->firstOrFail();
        $this->assertAuthenticatedAs($user, 'client');
        $this->assertSame($user->id, $builder->fresh()->user_id);
        $this->assertNotNull($builder->fresh()->email_verified_at);
    }

    public function test_wizard_renders_ten_steps_and_saves_each_step_via_json(): void
    {
        $this->seed();
        $this->verifiedClient();
        $this->post(route('builder.entry'), ['entry_path' => 'GUIDED']);

        $html = $this->get(route('builder.start'))->assertOk()->getContent();
        foreach (['Model', 'Gaya', 'Warna', 'Logo', 'Image', 'Domain', 'Rujukan', 'Add-on', 'Result', 'Nota'] as $i => $label) {
            $this->assertStringContainsString('Langkah '.($i + 1).' daripada 10', $html);
            $this->assertStringContainsString('<span class="bw-flow-label">'.$label.'</span>', $html);
        }
        $this->assertSame(1, substr_count($html, 'class="bw-flow"'));
        $this->assertStringContainsString('Kembali', $html);
        $this->assertStringContainsString('Simpan', $html);

        // Langkah "Model": kad kategori mesti sama seperti 6 kategori servis dalam katalog Pricing.
        foreach (['Website Development', 'E-Commerce', 'Custom Web Applications', 'AI &amp; Automation', 'System/API Integration', 'Maintenance &amp; Support'] as $category) {
            $this->assertStringContainsString($category, $html);
        }
        // Spec, kesesuaian dan add-on (boleh tick) berada dalam langkah "Model" (langkah 1), selepas dropdown pakej.
        $model = substr($html, strpos($html, 'id="bw-step-0"'), strpos($html, 'id="bw-step-1"') - strpos($html, 'id="bw-step-0"'));
        foreach (['id="service_package_id"', 'Spec pakej', 'Sesuai untuk:', 'aria-label="Add-on pakej"', '@change="toggleAddon(a)"'] as $needle) {
            $this->assertStringContainsString($needle, $model);
        }
        $this->assertStringNotContainsString('Fungsi yang sudah termasuk', $html);
        // Setiap pakej membawa servis (kategori) & add-on sendiri untuk penapisan dropdown dan senarai add-on.
        $data = collect(ServicePackage::query()->where('is_active', true)->with('service')->get())->pluck('service.slug')->unique()->sort()->values()->all();
        $this->assertSame(['ai-automation', 'custom-web-applications', 'e-commerce', 'maintenance-support', 'system-api-integration', 'website-development'], $data);

        $this->assertStringNotContainsString('Julat bajet', $html);
        $this->assertStringContainsString('Anggaran kos projek', $html);
        $pkg = ServicePackage::query()->where('slug', 'starter-website')->value('id');
        $blog = \App\Engines\Pricing\Models\Addon::query()->where('slug', 'blog-news')->value('id');
        $this->postJson(route('builder.save'), ['service_package_id' => $pkg, 'answers' => ['project_type' => 'website-development'], 'step' => 1])->assertOk()->assertJson(['saved' => true, 'total_cents' => 250000]);
        $this->postJson(route('builder.save'), ['service_package_id' => $pkg, 'answers' => ['colour_preference' => 'lain-lain', 'colour_custom' => '#12AB34', 'content_domain' => 'have-domain', 'domain_name' => 'contoh.com.my', 'content_images' => ['product-photos', 'people-photos']], 'step' => 3])->assertOk();
        // Add-on ditick terus dalam langkah "Add-on", ikut pakej dipilih (satu sumber, tiada soalan Fungsi).
        $this->postJson(route('builder.save'), ['service_package_id' => $pkg, 'answers' => ['content_images' => []], 'addon_ids' => [$blog], 'step' => 4])->assertOk()->assertJson(['total_cents' => 300000]);
        $page = \App\Engines\Pricing\Models\Addon::query()->where('slug', 'additional-page')->value('id');
        $this->postJson(route('builder.save'), ['service_package_id' => $pkg, 'addon_ids' => [$blog, $page]])->assertOk()->assertJson(['total_cents' => 335000]); // Starter: Blog RM500 + Additional page RM350
        $corporate = ServicePackage::query()->where('slug', 'corporate-website')->value('id');
        $this->postJson(route('builder.save'), ['service_package_id' => $corporate, 'addon_ids' => [$page]])->assertOk()->assertJson(['total_cents' => 740000]); // Blog tidak ditawarkan oleh Corporate (diabaikan); Additional page RM500 ikut pakej
        // Add-on yang tidak ditawarkan oleh pakej diabaikan; add-on bulanan tidak dicampur dalam jumlah.
        $advancedCms = \App\Engines\Pricing\Models\Addon::query()->where('slug', 'advanced-cms')->value('id');
        $maintenance = \App\Engines\Pricing\Models\Addon::query()->where('slug', 'maintenance-plan')->value('id');
        $this->postJson(route('builder.save'), ['service_package_id' => $pkg, 'addon_ids' => [$advancedCms, $maintenance]])->assertOk()->assertJson(['total_cents' => 250000]);
        $this->postJson(route('builder.save'), ['service_package_id' => $pkg, 'addon_ids' => [999999]])->assertStatus(422)->assertJsonValidationErrors('addon_ids.0');
        $this->postJson(route('builder.save'), ['service_package_id' => $pkg, 'answers' => ['project_type' => '']])->assertStatus(422)->assertJsonValidationErrors('answers.project_type');
        $this->postJson(route('builder.save'), ['service_package_id' => $pkg, 'addon_ids' => []])->assertOk()->assertJson(['total_cents' => 250000]); // buang add-on

        $session = BuilderSession::query()->firstOrFail();
        $answers = app(\App\Engines\Sales\Services\BuilderSessionService::class)->answers($session);
        $this->assertSame('website-development', $answers['project_type']);
        $this->assertSame('#12AB34', $answers['colour_custom']);
        $this->assertSame('contoh.com.my', $answers['domain_name']);
        $this->assertSame([], $answers['content_images']);
        $this->assertSame(4, session('natnetwork_builder_step'));
        $this->assertSame(4, $session->fresh()->current_step);
    }

    public function test_builder_files_are_validated_private_and_owned_by_the_session(): void
    {
        $this->seed();
        Storage::fake('local');
        $this->postJson(route('builder.files.store'), ['kind' => 'logo', 'file' => UploadedFile::fake()->image('logo.png')])->assertStatus(422);
        $this->verifiedClient();
        $this->post(route('builder.entry'), ['entry_path' => 'GUIDED']);

        $file = $this->postJson(route('builder.files.store'), ['kind' => 'logo', 'file' => UploadedFile::fake()->image('logo.png', 400, 200)])->assertCreated()->json();
        $this->postJson(route('builder.files.store'), ['kind' => 'logo', 'file' => UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream')])->assertStatus(422);
        $this->postJson(route('builder.files.store'), ['kind' => 'other', 'file' => UploadedFile::fake()->image('a.png')])->assertStatus(422);
        $this->get($file['url'])->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->flushSession();
        $this->app['auth']->guard('client')->logout();
        $this->get($file['url'])->assertNotFound();
        $this->deleteJson($file['url'])->assertNotFound();
        $this->assertDatabaseCount('builder_files', 1);
    }

    private function verifiedClient(): User
    {
        $user = User::factory()->create(['email' => 'aminah@example.test', 'phone' => '0123456789']);
        $this->actingAs($user, 'client');

        return $user;
    }
}
