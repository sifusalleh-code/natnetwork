<?php

namespace Tests\Feature;

use App\Adapters\Billplz\BillplzClient;
use App\Adapters\Email\OtpMailSender;
use App\Engines\Billing\Events\InvoicePaid;
use App\Engines\Billing\Listeners\CreateFinalInvoice;
use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Payment;
use App\Engines\Billing\Models\QuotationPaymentPlan;
use App\Engines\Billing\Services\ProjectPaymentService;
use App\Engines\Identity\Models\Admin;
use App\Engines\Pricing\Models\Addon;
use App\Engines\Project\Events\ProjectReachedReview;
use App\Engines\Project\Models\Project;
use App\Engines\Sales\Models\BuilderSession;
use App\Engines\Sales\Models\MasterSpecification;
use App\Engines\Sales\Models\Order;
use App\Engines\Sales\Models\ProjectRequest;
use App\Engines\Sales\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class StartProjectJourneyTest extends TestCase
{
    use RefreshDatabase;

    private array $codes = [];

    protected function setUp(): void
    {
        parent::setUp();
        $sender = Mockery::mock(OtpMailSender::class);
        $sender->shouldReceive('send')->andReturnUsing(function (string $email, string $code) { $this->codes[$email] = $code; });
        $this->app->instance(OtpMailSender::class, $sender);
    }

    public function test_identity_is_required_before_questions_and_verified_email_skips_code(): void
    {
        $this->seed();
        $this->post(route('builder.entry'), ['entry_path' => 'GUIDED'])->assertRedirect(route('builder.start'));
        $this->get(route('builder.start'))->assertOk()->assertSee('Maklumat anda')->assertDontSee('class="bw-flow"', false);
        $this->post(route('builder.save'), ['answers' => ['project_type' => 'company-website']])->assertSessionHasErrors('identity');

        // Emel baharu → kod pengesahan → soal jawab dibuka.
        $this->post(route('builder.identity'), ['name' => 'Ali', 'company' => null, 'email' => 'ali@example.test', 'phone' => '0123'])->assertSessionHasNoErrors();
        $this->assertArrayHasKey('ali@example.test', $this->codes);
        $this->post(route('builder.verify'), ['code' => $this->codes['ali@example.test']])->assertRedirect(route('builder.start'));
        $this->assertAuthenticated('client');
        $this->get(route('builder.start'))->assertOk()->assertSee('class="bw-flow"', false)->assertSee('Start Project anda');

        // Pelanggan yang sudah log masuk (emel disahkan): tiada kod baharu.
        $this->codes = [];
        $this->post(route('builder.new'))->assertRedirect(route('builder.start'));
        $this->post(route('builder.entry'), ['entry_path' => 'GUIDED']);
        $this->get(route('builder.start'))->assertSee('class="bw-flow"', false);
        $this->assertSame([], $this->codes);
    }

    public function test_progress_is_autosaved_and_resumed_within_thirty_days_on_another_device(): void
    {
        $this->seed();
        $user = User::factory()->create(['email' => 'siti@example.test', 'email_verified_at' => now()]);
        $this->actingAs($user, 'client')->post(route('builder.entry'), ['entry_path' => 'GUIDED']);
        $this->postJson(route('builder.save'), ['service_package_id' => \App\Engines\Pricing\Models\ServicePackage::query()->value('id'), 'answers' => ['project_type' => 'company-website'], 'step' => 3])->assertOk();
        $saved = BuilderSession::query()->where('user_id', $user->id)->sole();
        $this->assertSame(3, $saved->current_step);

        // Peranti lain: sesi kosong → isi emel → kod → Start Project tersimpan dibuka semula.
        $this->app['auth']->guard('client')->logout();
        $this->flushSession();
        $this->post(route('builder.entry'), ['entry_path' => 'GUIDED']);
        $this->post(route('builder.identity'), ['name' => 'Siti', 'company' => null, 'email' => 'siti@example.test', 'phone' => '0199']);
        $this->post(route('builder.verify'), ['code' => $this->codes['siti@example.test']])->assertSessionHas('builder_status', fn ($m) => str_contains($m, 'dibuka semula'));
        $this->assertSame($saved->id, session('natnetwork_builder_session_id'));
        $this->get(route('builder.start'))->assertOk()->assertSee('3 / 10 langkah');

        // Lebih 30 hari tidak aktif → tidak disambung.
        BuilderSession::query()->whereKey($saved->id)->update(['updated_at' => now()->subDays(31)]);
        $this->flushSession();
        $this->actingAs($user, 'client')->get(route('builder.start'))->assertOk()->assertSee('Saya mahu panduan');
    }

    public function test_full_payment_gets_admin_reward_and_no_balance_invoice(): void
    {
        $this->configureProduction();
        $addon = Addon::query()->create(['slug' => 'blog', 'name' => 'Blog / News', 'price_type' => 'starting', 'price_amount' => 600, 'price_label' => 'RM600+', 'is_active' => true]);
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->put(route('admin.billing.settings.reward'), ['enabled' => '1', 'type' => 'percent', 'value' => '150'])->assertSessionHasErrors('value');
        $this->actingAs($admin, 'admin')->put(route('admin.billing.settings.reward'), ['enabled' => '1', 'type' => 'addon'])->assertSessionHasErrors('addon_id');
        $this->actingAs($admin, 'admin')->put(route('admin.billing.settings.reward'), ['enabled' => '1', 'type' => 'percent', 'value' => '5'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('audit_logs', ['action' => 'FULL_PAYMENT_REWARD_CHANGED']);

        [$client, $quotation] = $this->acceptedQuotation('6000.00');
        $this->actingAs($client, 'client')->get(route('client.quotations.slot', $quotation))->assertOk()->assertSee('Bayar penuh 100%')->assertSee('RM 5,700.00')->assertSee('Diskaun 5%')->assertSee('RM 3,000.00');
        $this->actingAs($client, 'client')->post(route('client.quotations.slot.hold', $quotation), ['start_date' => now()->next('Monday')->toDateString()])->assertSessionHasErrors('plan');
        $this->actingAs($client, 'client')->post(route('client.quotations.slot.hold', $quotation), ['start_date' => now()->next('Monday')->toDateString(), 'plan' => 'FULL'])->assertSessionHasNoErrors();

        $invoice = Invoice::query()->where('source_type', 'Quotation')->where('source_id', $quotation->id)->sole();
        $this->assertSame('5700.00', $invoice->total);
        $this->assertStringContainsString('Bayaran penuh 100%', $invoice->items_snapshot[0]['description']);
        $this->assertSame($invoice->id, QuotationPaymentPlan::query()->sole()->invoice_id);

        // Tetapan admin berubah selepas invois: invois & pilihan pelanggan tidak berubah.
        $this->actingAs($admin, 'admin')->put(route('admin.billing.settings.reward'), ['enabled' => '1', 'type' => 'addon', 'addon_id' => $addon->id]);
        $this->actingAs($client, 'client')->get(route('client.quotations.slot', $quotation))->assertSee('Diskaun 5%')->assertDontSee('Percuma: Blog');

        $this->pay($client, $invoice, 570000);
        $order = Order::query()->where('quotation_id', $quotation->id)->sole();
        $this->assertSame(Order::CONFIRMED, $order->status);

        // Progress 80%: tiada invois baki; pengesahan bayaran penuh menggunakan nilai selepas diskaun.
        $project = Project::query()->where('quotation_id', $quotation->id)->sole();
        app(CreateFinalInvoice::class)->handle(new ProjectReachedReview($project));
        $this->assertSame(1, Invoice::query()->where('source_type', 'Quotation')->where('source_id', $quotation->id)->count());
        $this->assertTrue(app(ProjectPaymentService::class)->summary($quotation->fresh())['can_confirm']);

        // Selepas bayaran berjaya: halaman status → Portal.
        $this->actingAs($client, 'client')->get(route('billing.billplz.return', ['billplz' => ['id' => 'bill1', 'paid' => 'true', 'paid_at' => '2026-09-25', 'x_signature' => 'x']]))->assertOk();
    }

    public function test_deposit_plan_and_free_addon_reward_label(): void
    {
        BillingSetting::current()->forceFill(['full_payment_reward_enabled' => true, 'full_payment_reward_type' => 'addon', 'full_payment_reward_addon_id' => Addon::query()->create(['slug' => 'seo', 'name' => 'SEO Asas', 'price_type' => 'starting', 'price_amount' => 500, 'price_label' => 'RM500+', 'is_active' => true])->id])->save();
        [$client, $quotation] = $this->acceptedQuotation('3900.00');
        $this->actingAs($client, 'client')->get(route('client.quotations.slot', $quotation))->assertSee('Percuma: SEO Asas')->assertSee('RM 3,900.00')->assertSee('RM 1,950.00');
        $this->actingAs($client, 'client')->post(route('client.quotations.slot.hold', $quotation), ['start_date' => now()->next('Monday')->toDateString(), 'plan' => 'DEPOSIT'])->assertSessionHasNoErrors();
        $this->assertSame('1950.00', Invoice::query()->where('source_id', $quotation->id)->sole()->total);
    }

    public function test_failed_payment_can_continue_same_bill_or_reset_start_project(): void
    {
        $this->configureProduction();
        [$client, $quotation, $builder] = $this->acceptedQuotation('4000.00', true);
        $this->actingAs($client, 'client')->post(route('client.quotations.slot.hold', $quotation), ['start_date' => now()->next('Monday')->toDateString(), 'plan' => 'DEPOSIT']);
        $invoice = Invoice::query()->where('source_id', $quotation->id)->sole();

        Http::fake(['www.billplz.com/api/v3/bills' => Http::response(['id' => 'bill1', 'url' => 'https://www.billplz.com/bills/bill1'])]);
        $this->actingAs($client, 'client')->post(route('client.billing.pay', $invoice))->assertRedirect('https://www.billplz.com/bills/bill1');
        $this->billplzCallback('bill1', 200000, false);
        $this->assertSame(Payment::STATUS_FAILED, Payment::query()->sole()->status);

        // Datang semula: pilihan teruskan bil sama atau reset.
        $html = $this->actingAs($client, 'client')->withSession(['natnetwork_builder_session_id' => $builder->id])->get(route('builder.start'))->assertOk()->getContent();
        $this->assertStringContainsString('Teruskan bayaran (bil sama)', $html);
        $this->assertStringContainsString('Reset &amp; mula semula', $html);
        $this->assertStringContainsString(route('client.billing.invoice', $invoice), $html);

        $this->actingAs($client, 'client')->withSession(['natnetwork_builder_session_id' => $builder->id])->post(route('builder.reset'))->assertSessionHasErrors('confirm');
        $this->actingAs($client, 'client')->withSession(['natnetwork_builder_session_id' => $builder->id])->post(route('builder.reset'), ['confirm' => '1'])->assertRedirect(route('builder.start'));

        $this->assertSame(Invoice::STATUS_VOID, $invoice->fresh()->status);
        $this->assertSame(Quotation::STATUS_CANCELLED, $quotation->fresh()->status);
        $this->assertDatabaseHas('slot_holds', ['quotation_id' => $quotation->id, 'status' => 'RELEASED']);
        $this->assertNotNull($builder->fresh()->reset_at);
        $this->assertDatabaseHas('payments', ['status' => Payment::STATUS_FAILED]); // rekod gagal disimpan
        $this->assertDatabaseHas('audit_logs', ['action' => 'START_PROJECT_RESET']);
        $this->assertNotSame($builder->id, session('natnetwork_builder_session_id'));
        $this->actingAs($client, 'client')->get(route('builder.start'))->assertSee('Saya mahu panduan');
    }

    public function test_reset_is_blocked_after_successful_payment(): void
    {
        $this->configureProduction();
        [$client, $quotation, $builder] = $this->acceptedQuotation('4000.00', true);
        $this->actingAs($client, 'client')->post(route('client.quotations.slot.hold', $quotation), ['start_date' => now()->next('Monday')->toDateString(), 'plan' => 'DEPOSIT']);
        $invoice = Invoice::query()->where('source_id', $quotation->id)->sole();
        $this->pay($client, $invoice, 200000);

        $this->actingAs($client, 'client')->withSession(['natnetwork_builder_session_id' => $builder->id])->get(route('builder.start'))->assertSee('Masuk ke Portal')->assertDontSee('Reset &amp; mula semula', false);
        $this->actingAs($client, 'client')->withSession(['natnetwork_builder_session_id' => $builder->id])->post(route('builder.reset'), ['confirm' => '1'])->assertSessionHasErrors('reset');
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    public function test_payment_on_voided_invoice_needs_review(): void
    {
        $this->configureProduction();
        [$client, $quotation] = $this->acceptedQuotation('4000.00');
        $this->actingAs($client, 'client')->post(route('client.quotations.slot.hold', $quotation), ['start_date' => now()->next('Monday')->toDateString(), 'plan' => 'DEPOSIT']);
        $invoice = Invoice::query()->where('source_id', $quotation->id)->sole();
        Http::fake(['www.billplz.com/api/v3/bills' => Http::response(['id' => 'bill9', 'url' => 'https://www.billplz.com/bills/bill9'])]);
        $this->actingAs($client, 'client')->post(route('client.billing.pay', $invoice));
        $invoice->forceFill(['status' => Invoice::STATUS_VOID, 'voided_at' => now()])->save();

        $this->billplzCallback('bill9', 200000, true);
        $this->assertSame(Payment::STATUS_REVIEW_REQUIRED, Payment::query()->sole()->status);
        $this->assertSame(Invoice::STATUS_VOID, $invoice->fresh()->status);
    }

    private function pay(User $client, Invoice $invoice, int $cents): void
    {
        $bill = 'bill'.$invoice->id;
        Http::fake(['www.billplz.com/api/v3/bills' => Http::response(['id' => $bill, 'url' => 'https://www.billplz.com/bills/'.$bill])]);
        $this->actingAs($client, 'client')->post(route('client.billing.pay', $invoice))->assertRedirect('https://www.billplz.com/bills/'.$bill);
        $this->billplzCallback($bill, $cents, true);
        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
    }

    private function billplzCallback(string $bill, int $cents, bool $paid): void
    {
        $payload = ['id' => $bill, 'collection_id' => 'col_prod', 'paid' => $paid ? 'true' : 'false', 'state' => $paid ? 'paid' : 'due', 'amount' => (string) $cents, 'paid_amount' => $paid ? (string) $cents : '0',
            'due_at' => '2026-9-25', 'email' => 'x@example.test', 'mobile' => '', 'name' => 'X', 'url' => 'https://www.billplz.com/bills/'.$bill, 'paid_at' => $paid ? '2026-09-25 10:00:00 +0800' : ''];
        $payload['x_signature'] = app(BillplzClient::class)->callbackSignature($payload, 'S-prod');
        $this->post(route('billing.billplz.callback'), $payload)->assertOk();
    }

    private function acceptedQuotation(string $total, bool $withBuilder = false): array
    {
        $client = User::factory()->create(['email_verified_at' => now()]);
        $builder = BuilderSession::query()->create(['entry_path' => 'GUIDED', 'user_id' => $client->id, 'contact_name' => $client->name, 'contact_email' => $client->email, 'contact_phone' => '012', 'email_verified_at' => now(), 'current_step' => 10]);
        $request = ProjectRequest::query()->create(['customer_user_id' => $client->id, 'builder_session_id' => $builder->id, 'status' => 'DRAFT', 'requirement_snapshot' => []]);
        $spec = MasterSpecification::query()->create(['project_request_id' => $request->id, 'version' => 1, 'status' => 'APPROVED', 'requirement_snapshot' => [], 'specification_snapshot' => ['project_summary' => []], 'approved_at' => now()]);
        $quotation = Quotation::query()->create([
            'project_request_id' => $request->id, 'master_specification_id' => $spec->id, 'status' => Quotation::STATUS_ACCEPTED,
            'number' => 'NAT-QT-T-'.$request->id, 'total_amount' => $total, 'estimated_weeks' => 3, 'accepted_at' => now(),
            'price_snapshot' => ['selected_package' => ['name' => 'Website'], 'project_template' => 'website', 'items' => [['description' => 'Website', 'quantity' => 1, 'unit_price' => $total, 'line_total' => $total]]],
            'terms_snapshot' => config('sales_terms'),
        ]);

        return $withBuilder ? [$client, $quotation, $builder] : [$client, $quotation];
    }

    private function configureProduction(): void
    {
        BillingSetting::current()->forceFill(['active_mode' => 'PRODUCTION', 'production_api_key' => 'prod', 'production_collection_id' => 'col_prod', 'production_x_signature_key' => 'S-prod'])->save();
    }

    private function admin(): Admin
    {
        $admin = Admin::query()->create(['name' => 'Nat', 'email' => 'admin@example.test', 'password' => 'password-yang-panjang']);
        $admin->forceFill(['two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'two_factor_confirmed_at' => now()])->save();

        return $admin;
    }
}
