<?php

namespace Tests\Feature;

use App\Adapters\Billplz\BillplzClient;
use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliateClientLink;
use App\Engines\Affiliate\Models\AffiliateCommission;
use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Payment;
use App\Engines\Identity\Models\Admin;
use App\Engines\Project\Models\Project;
use App\Engines\Sales\Models\BuilderSession;
use App\Engines\Sales\Models\MasterSpecification;
use App\Engines\Sales\Models\Order;
use App\Engines\Sales\Models\ProjectRequest;
use App\Engines\Sales\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_portal_menus_render_for_client_and_redirect_guests(): void
    {
        $client = User::factory()->create();
        $routes = ['client.dashboard', 'client.projects', 'client.quotations', 'client.billing', 'client.files', 'client.support', 'client.notifications', 'client.profile'];

        foreach ($routes as $route) {
            $this->get(route($route))->assertRedirect(route('client.login'));
        }
        // Sebelum bayaran pertama: hanya langkah Start Project (quotation, billing, notifikasi, profil).
        foreach (['client.dashboard', 'client.projects', 'client.files', 'client.support'] as $route) {
            $this->actingAs($client, 'client')->get(route($route))->assertRedirect(route('builder.start'));
        }
        $this->actingAs($client, 'client')->get(route('client.quotations'))->assertOk()->assertSee('Start Project')->assertDontSee('>Projects<', false);
        $this->portalReadyClient($client);
        foreach ($routes as $route) {
            $this->actingAs($client, 'client')->get(route($route))->assertOk()->assertSee('Portal Pelanggan');
        }
        $this->actingAs($client, 'client')->get(route('client.dashboard'))->assertSee('sehingga 10%');
    }

    public function test_full_flow_commission_released_only_after_admin_confirms_full_project_payment(): void
    {
        $this->configureProduction();
        [$client, $quotation] = $this->quotationForNewClient();
        $affiliate = Affiliate::query()->create(['name' => 'Aff', 'email' => 'aff@example.test', 'phone' => '012', 'username' => 'aff']);
        AffiliateClientLink::query()->create(['affiliate_id' => $affiliate->id, 'customer_user_id' => $client->id, 'linked_at' => now()]);

        // Quotation REVIEW REQUIRED tidak kelihatan kepada pelanggan
        $this->actingAs($client, 'client')->get(route('client.quotations.show', $quotation))->assertNotFound();

        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->post(route('admin.sales.quotation.send', $quotation), [
            'items' => [['description' => 'Laman web korporat', 'quantity' => 1, 'unit_price' => '8000'], ['description' => 'Blog', 'quantity' => 1, 'unit_price' => '2000']],
            'valid_until' => now()->addDays(14)->toDateString(),
            'estimated_weeks' => 4, 'project_template' => 'website',
            'confirm' => '1',
        ])->assertRedirect(route('admin.sales.quotation', $quotation));
        $quotation->refresh();
        $this->assertSame(Quotation::STATUS_SENT, $quotation->status);
        $this->assertSame('10000.00', $quotation->total_amount);
        $this->assertStringStartsWith('NAT-QT-', $quotation->number);

        $this->actingAs($client, 'client')->get(route('client.dashboard'))->assertRedirect(route('builder.start')); // portal dibuka selepas bayaran
        $this->actingAs($client, 'client')->get(route('client.quotations.show', $quotation))->assertOk()->assertSee('Laman web korporat')->assertSee('RM 10,000.00');
        $this->assertSame(Quotation::STATUS_VIEWED, $quotation->fresh()->status);

        $this->actingAs($client, 'client')->post(route('client.quotations.accept', $quotation))->assertSessionHasErrors('agree');
        $response = $this->actingAs($client, 'client')->post(route('client.quotations.accept', $quotation), ['agree' => '1']);
        $this->actingAs($client, 'client')->post(route('client.quotations.accept', $quotation), ['agree' => '1']);

        // Terima → pilih slot. Invois deposit hanya dicipta selepas slot dipegang.
        $response->assertRedirect(route('client.quotations.slot', $quotation));
        $this->assertSame(Quotation::STATUS_ACCEPTED, $quotation->fresh()->status);
        $this->assertDatabaseCount('invoices', 0);
        $this->actingAs($client, 'client')->get(route('client.dashboard'))->assertRedirect(route('builder.start'));
        $start = now()->next('Monday')->toDateString();
        $this->actingAs($client, 'client')->get(route('client.quotations.slot', $quotation))->assertOk()->assertSee('Minggu tersedia');
        $hold = $this->actingAs($client, 'client')->post(route('client.quotations.slot.hold', $quotation), ['start_date' => $start, 'plan' => 'DEPOSIT']);

        $invoice = Invoice::query()->sole();
        $hold->assertRedirect(route('client.billing.invoice', $invoice));
        $this->assertDatabaseHas('slot_holds', ['quotation_id' => $quotation->id, 'status' => 'HELD']);
        $this->assertSame('5000.00', $invoice->total);
        $this->assertSame('DEPOSIT', $invoice->type);
        $this->assertSame($client->id, $invoice->customer_user_id);
        $this->assertFalse($invoice->is_sandbox);
        $this->assertSame('350.00', AffiliateCommission::query()->sole()->amount);
        $this->assertDatabaseHas('audit_logs', ['action' => 'QUOTATION_ACCEPTED']);

        Http::fake(['www.billplz.com/api/v3/bills' => Http::sequence()->push(['id' => 'prodbill', 'url' => 'https://www.billplz.com/bills/prodbill'])->push(['id' => 'finalbill', 'url' => 'https://www.billplz.com/bills/finalbill'])]);
        $this->actingAs($client, 'client')->post(route('client.billing.pay', $invoice))->assertRedirect('https://www.billplz.com/bills/prodbill');

        $payment = Payment::query()->sole();
        $payload = ['id' => 'prodbill', 'collection_id' => 'col_prod', 'paid' => 'true', 'state' => 'paid', 'amount' => '500000', 'paid_amount' => '500000',
            'due_at' => '2026-9-24', 'email' => $client->email, 'mobile' => '', 'name' => $client->name, 'url' => $payment->gateway_url, 'paid_at' => '2026-09-24 10:00:00 +0800'];
        $payload['x_signature'] = app(BillplzClient::class)->callbackSignature($payload, 'S-prod');
        $this->post(route('billing.billplz.callback'), $payload)->assertOk();
        $this->post(route('billing.billplz.callback'), $payload)->assertOk();

        $this->assertSame(Invoice::STATUS_PAID, $invoice->fresh()->status);
        $this->assertSame('PENDING', AffiliateCommission::query()->sole()->status);

        // Deposit disahkan → slot RESERVED → ORDER CONFIRMED → projek WAITING_TO_START (sekali sahaja)
        $this->assertDatabaseHas('slot_holds', ['quotation_id' => $quotation->id, 'status' => 'RESERVED']);
        $order = Order::query()->sole();
        $this->assertStringStartsWith('NAT-ORD-', $order->number);
        $project = Project::query()->sole();
        $this->assertStringStartsWith('NAT-PRJ-', $project->number);
        $this->assertSame(Project::WAITING_TO_START, $project->status);
        $this->assertSame(100, (int) $project->milestones()->sum('weight'));
        $this->actingAs($client, 'client')->get(route('client.projects.show', $project))->assertOk()->assertSee('Menunggu mula')->assertSee('Logo syarikat');
        $this->actingAs($client, 'client')->get(route('client.notifications'))->assertSee('Pesanan '.$order->number.' disahkan');

        // Belum boleh sahkan: baki belum diinvois/dibayar
        $this->actingAs($admin, 'admin')->post(route('admin.sales.quotation.confirm-payment', $quotation), ['confirm' => '1'])->assertSessionHasErrors('confirm');

        // Milestone sebelum START PROJECT ditolak
        $milestones = $project->milestones()->get();
        $this->actingAs($admin, 'admin')->post(route('admin.projects.milestones.complete', $milestones[0]))->assertSessionHasErrors('milestone');
        $this->actingAs($admin, 'admin')->post(route('admin.projects.start', $project))->assertSessionHasNoErrors();
        $this->actingAs($admin, 'admin')->post(route('admin.projects.start', $project))->assertSessionHasErrors('project');
        $this->assertSame(Project::IN_PROGRESS, $project->fresh()->status);

        // 5 milestone pertama = 75%: tiada invois akhir. Milestone ke-6 melepasi 80% → invois akhir sekali.
        foreach ($milestones->take(5) as $m) {
            $this->actingAs($admin, 'admin')->post(route('admin.projects.milestones.complete', $m))->assertSessionHasNoErrors();
        }
        $this->assertSame(75, $project->fresh()->progress);
        $this->assertSame(0, Invoice::query()->where('type', 'FINAL_PAYMENT')->count());
        $this->actingAs($admin, 'admin')->post(route('admin.projects.milestones.complete', $milestones[5]));
        $this->actingAs($admin, 'admin')->post(route('admin.projects.milestones.complete', $milestones[5]));
        $this->actingAs($admin, 'admin')->post(route('admin.projects.milestones.complete', $milestones[6]));
        $this->assertSame(90, $project->fresh()->progress);
        $final = Invoice::query()->where('type', 'FINAL_PAYMENT')->sole();
        $this->assertSame($final->id, $project->fresh()->final_invoice_id);
        $this->assertSame('5000.00', $final->total);
        $this->assertSame('200.00', AffiliateCommission::query()->where('invoice_id', $final->id)->value('amount'));

        $this->actingAs($client, 'client')->post(route('client.billing.pay', $final))->assertRedirect('https://www.billplz.com/bills/finalbill');
        $finalPayment = Payment::query()->where('invoice_id', $final->id)->sole();
        $payload2 = array_merge($payload, ['id' => 'finalbill', 'url' => $finalPayment->gateway_url]);
        unset($payload2['x_signature']);
        $payload2['x_signature'] = app(BillplzClient::class)->callbackSignature($payload2, 'S-prod');
        $this->post(route('billing.billplz.callback'), $payload2)->assertOk();
        $this->assertSame(Invoice::STATUS_PAID, $final->fresh()->status);
        $this->assertSame(2, AffiliateCommission::query()->where('status', 'PENDING')->count());
        $this->assertSame(1, Order::query()->count());

        $this->actingAs($admin, 'admin')->get(route('admin.sales.quotation', $quotation))->assertOk()->assertSee('Sahkan bayaran projek selesai');
        $this->actingAs($admin, 'admin')->post(route('admin.sales.quotation.confirm-payment', $quotation))->assertSessionHasErrors('confirm');
        $this->actingAs($admin, 'admin')->post(route('admin.sales.quotation.confirm-payment', $quotation), ['confirm' => '1'])->assertSessionHasNoErrors();
        $this->actingAs($admin, 'admin')->post(route('admin.sales.quotation.confirm-payment', $quotation), ['confirm' => '1'])->assertSessionHasNoErrors();

        $this->assertSame(2, AffiliateCommission::query()->where('status', 'RELEASED')->count());
        $this->assertNotNull($quotation->fresh()->payment_completed_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'PROJECT_PAYMENT_CONFIRMED']);
        $receipt = $invoice->fresh()->receipts()->sole();
        $this->actingAs($client, 'client')->get(route('client.billing'))->assertSee($receipt->number)->assertSee('Dibayar');
        $this->actingAs($client, 'client')->get(route('client.billing.receipt', $receipt))->assertOk()->assertSee('RM 5,000.00');

        // Selesai: perlu READY_FOR_HANDOVER + 100%
        $this->actingAs($admin, 'admin')->post(route('admin.projects.complete', $project), ['confirm' => '1'])->assertSessionHasErrors('project');
        foreach (['CLIENT_REVIEW', 'FINAL_APPROVAL', 'READY_FOR_HANDOVER'] as $to) {
            $this->actingAs($admin, 'admin')->post(route('admin.projects.transition', $project), ['to' => $to])->assertSessionHasNoErrors();
        }
        $this->actingAs($admin, 'admin')->post(route('admin.projects.complete', $project), ['confirm' => '1'])->assertSessionHasErrors('project');
        foreach ($milestones->slice(7) as $m) {
            $this->actingAs($admin, 'admin')->post(route('admin.projects.milestones.complete', $m));
        }
        $this->actingAs($admin, 'admin')->post(route('admin.projects.complete', $project), ['confirm' => '1'])->assertSessionHasNoErrors();
        $project->refresh();
        $this->assertSame(Project::COMPLETED, $project->status);
        $this->assertSame(60, $project->support_days);
        $this->assertDatabaseHas('slot_holds', ['quotation_id' => $quotation->id, 'status' => 'RELEASED']);
        $this->actingAs($client, 'client')->get(route('client.support'))->assertSee('Sokongan percuma aktif sehingga '.$project->supportEndsAt()->format('d/m/Y'));
    }

    public function test_client_cannot_access_another_clients_quotation_invoice_or_receipt(): void
    {
        $this->configureProduction();
        [$owner, $quotation] = $this->quotationForNewClient();
        $this->sendQuotation($quotation);
        $this->actingAs($owner, 'client')->post(route('client.quotations.accept', $quotation), ['agree' => '1']);
        $this->actingAs($intruder = User::factory()->create(), 'client')->get(route('client.quotations.slot', $quotation))->assertNotFound();
        $this->actingAs($intruder, 'client')->post(route('client.quotations.slot.hold', $quotation), ['start_date' => now()->next('Monday')->toDateString(), 'plan' => 'DEPOSIT'])->assertNotFound();
        $this->actingAs($owner, 'client')->post(route('client.quotations.slot.hold', $quotation), ['start_date' => now()->next('Monday')->toDateString(), 'plan' => 'DEPOSIT']);
        $invoice = Invoice::query()->sole();

        $this->actingAs($intruder, 'client')->get(route('client.quotations.show', $quotation))->assertNotFound();
        $this->actingAs($intruder, 'client')->post(route('client.quotations.accept', $quotation), ['agree' => '1'])->assertNotFound();
        $this->actingAs($intruder, 'client')->get(route('client.billing.invoice', $invoice))->assertNotFound();
        $this->actingAs($intruder, 'client')->post(route('client.billing.pay', $invoice))->assertNotFound();
        $this->actingAs($intruder, 'client')->get(route('client.billing'))->assertDontSee($invoice->number);
    }

    public function test_expired_quotation_cannot_be_accepted(): void
    {
        $this->configureProduction();
        [$client, $quotation] = $this->quotationForNewClient();
        $this->sendQuotation($quotation);
        $quotation->forceFill(['valid_until' => now()->subDay()])->save();

        $this->actingAs($client, 'client')->post(route('client.quotations.accept', $quotation), ['agree' => '1'])->assertSessionHasErrors('quotation');

        $this->assertSame(Quotation::STATUS_EXPIRED, $quotation->fresh()->status);
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_sent_quotation_is_locked_for_admin(): void
    {
        $this->configureProduction();
        [, $quotation] = $this->quotationForNewClient();
        $this->sendQuotation($quotation);
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post(route('admin.sales.quotation.send', $quotation), [
            'items' => [['description' => 'Murah', 'quantity' => 1, 'unit_price' => '1']], 'valid_until' => now()->addDay()->toDateString(), 'estimated_weeks' => 4, 'project_template' => 'website', 'confirm' => '1',
        ])->assertSessionHasErrors('quotation');
        $this->assertSame('10000.00', $quotation->fresh()->total_amount);
        $this->actingAs($admin, 'admin')->get(route('admin.sales.quotation', $quotation))->assertOk()->assertDontSee('Hantar kepada pelanggan');
    }

    public function test_client_profile_update(): void
    {
        $client = User::factory()->create(['email' => 'c@example.test']);

        $this->actingAs($client, 'client')->put(route('client.profile.update'), ['name' => '', 'phone' => ''])->assertSessionHasErrors(['name', 'phone']);
        $this->actingAs($client, 'client')->put(route('client.profile.update'), ['name' => 'Nama Baru', 'company' => 'Syarikat', 'phone' => '0123', 'email' => 'hack@example.test'])
            ->assertRedirect(route('client.profile'));

        $client->refresh();
        $this->assertSame('Nama Baru', $client->name);
        $this->assertSame('c@example.test', $client->email);
    }

    /** @return array{0: User, 1: Quotation} */
    private function quotationForNewClient(): array
    {
        $client = User::factory()->create(['phone' => '0123456789']);
        $builder = BuilderSession::query()->create(['entry_path' => 'GUIDED', 'user_id' => $client->id, 'contact_name' => $client->name, 'contact_email' => $client->email, 'contact_phone' => '012']);
        $request = ProjectRequest::query()->create(['customer_user_id' => $client->id, 'builder_session_id' => $builder->id, 'status' => 'DRAFT', 'requirement_snapshot' => []]);
        $spec = MasterSpecification::query()->create(['project_request_id' => $request->id, 'version' => 1, 'status' => 'APPROVED', 'requirement_snapshot' => [], 'specification_snapshot' => ['project_summary' => []], 'approved_at' => now()]);
        $quotation = Quotation::query()->create(['project_request_id' => $request->id, 'master_specification_id' => $spec->id, 'status' => Quotation::STATUS_REVIEW_REQUIRED,
            'price_snapshot' => ['selected_package' => null], 'terms_snapshot' => config('sales_terms')]);

        return [$client, $quotation];
    }

    private function sendQuotation(Quotation $quotation): void
    {
        $this->actingAs($this->admin(), 'admin')->post(route('admin.sales.quotation.send', $quotation), [
            'items' => [['description' => 'Projek', 'quantity' => 1, 'unit_price' => '10000']], 'valid_until' => now()->addDays(7)->toDateString(), 'estimated_weeks' => 4, 'project_template' => 'website', 'confirm' => '1',
        ])->assertSessionHasNoErrors();
    }

    private function configureProduction(): void
    {
        BillingSetting::current()->forceFill([
            'active_mode' => 'PRODUCTION',
            'production_api_key' => 'prod', 'production_collection_id' => 'col_prod', 'production_x_signature_key' => 'S-prod',
        ])->save();
    }

    private function admin(): Admin
    {
        $admin = Admin::query()->firstOrCreate(['email' => 'admin@example.test'], ['name' => 'Nat', 'password' => 'password-yang-panjang']);
        $admin->forceFill(['two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'two_factor_confirmed_at' => now()])->save();

        return $admin;
    }
}
