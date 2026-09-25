<?php

namespace Tests\Feature;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliateClientLink;
use App\Engines\Affiliate\Models\AffiliateCommission;
use App\Engines\Billing\Models\BillingSetting;
use App\Engines\Billing\Models\Invoice;
use App\Engines\Billing\Models\Payment;
use App\Engines\Billing\Models\Refund;
use App\Engines\Billing\Services\ProjectPaymentService;
use App\Engines\Communication\Models\CommunicationSetting;
use App\Engines\Communication\Models\EmailLog;
use App\Engines\Identity\Models\Admin;
use App\Engines\Project\Models\Project;
use App\Engines\Project\Services\ProjectService;
use App\Engines\Sales\Models\BuilderSession;
use App\Engines\Sales\Models\ChangeRequest;
use App\Engines\Sales\Models\MasterSpecification;
use App\Engines\Sales\Models\Order;
use App\Engines\Sales\Models\ProjectRequest;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Scheduling\Models\SchedulingSetting;
use App\Engines\Scheduling\Models\SlotHold;
use App\Engines\Scheduling\Services\SchedulingService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProjectExtensionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_refund_before_start_cancels_project_order_slot_and_commission(): void
    {
        [$client, $project] = $this->confirmedProject(withAffiliate: true);
        $admin = $this->admin();
        $deposit = $this->payDeposit($project);
        $this->assertSame('PENDING', AffiliateCommission::query()->where('invoice_id', $deposit->id)->value('status'));

        // Melebihi baki boleh refund ditolak
        $this->actingAs($admin, 'admin')->post(route('admin.projects.refund', $project), ['amounts' => [$deposit->id => '3000.01'], 'reason' => 'x', 'transfer_reference' => 'T1', 'confirm' => '1'])->assertSessionHasErrors('refund');
        $this->actingAs($admin, 'admin')->post(route('admin.projects.refund', $project), ['amounts' => [$deposit->id => '2900'], 'reason' => 'Pelanggan batal', 'transfer_reference' => 'MBB123'])->assertSessionHasErrors('confirm');
        $this->actingAs($admin, 'admin')->post(route('admin.projects.refund', $project), ['amounts' => [$deposit->id => '2900'], 'reason' => 'Pelanggan batal', 'transfer_reference' => 'MBB123', 'confirm' => '1'])->assertSessionHasNoErrors();

        $refund = Refund::query()->sole();
        $this->assertStringStartsWith('NAT-RF-', $refund->number);
        $this->assertSame('2900.00', $deposit->fresh()->amount_refunded);
        $this->assertSame(Payment::STATUS_PARTIALLY_REFUNDED, Payment::query()->where('invoice_id', $deposit->id)->value('status'));
        $this->assertSame(Project::CANCELLED, $project->fresh()->status);
        $this->assertSame(Order::CANCELLED, Order::query()->sole()->status);
        $this->assertSame('RELEASED', SlotHold::query()->sole()->status);
        $this->assertSame('CANCELLED', AffiliateCommission::query()->where('invoice_id', $deposit->id)->value('status'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'PROJECT_REFUNDED']);

        // Tiada refund kedua (projek ditutup)
        $this->actingAs($admin, 'admin')->post(route('admin.projects.refund', $project), ['amounts' => [$deposit->id => '100'], 'reason' => 'x', 'transfer_reference' => 'T2', 'confirm' => '1'])->assertSessionHasErrors('refund');
        $this->actingAs($client, 'client')->get(route('client.billing.invoice', $deposit))->assertSee($refund->number)->assertSee('RM 2,900.00');
        $this->assertDatabaseHas('portal_notifications', ['recipient_id' => $client->id, 'title' => 'Refund RM 2,900.00 untuk '.$project->number]);
    }

    public function test_refund_not_allowed_after_start_project(): void
    {
        [, $project] = $this->confirmedProject();
        $admin = $this->admin();
        $deposit = $this->payDeposit($project);
        $this->actingAs($admin, 'admin')->post(route('admin.projects.start', $project));

        $this->actingAs($admin, 'admin')->post(route('admin.projects.refund', $project), ['amounts' => [$deposit->id => '100'], 'reason' => 'x', 'transfer_reference' => 'T', 'confirm' => '1'])->assertSessionHasErrors('refund');
        $this->assertDatabaseCount('refunds', 0);
        $this->actingAs($admin, 'admin')->get(route('admin.projects.show', $project))->assertOk()->assertDontSee('Rekod refund');
    }

    public function test_milestones_editable_only_before_start_and_must_total_100(): void
    {
        [, $project] = $this->confirmedProject();
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->put(route('admin.projects.milestones.update', $project), ['milestones' => [['name' => 'A', 'client_label' => 'A', 'weight' => 50], ['name' => 'B', 'client_label' => '', 'weight' => 40]]])->assertSessionHasErrors('milestones');
        $this->actingAs($admin, 'admin')->put(route('admin.projects.milestones.update', $project), ['milestones' => [['name' => 'Reka bentuk', 'client_label' => 'Reka Bentuk', 'weight' => 40], ['name' => 'Bina', 'client_label' => '', 'weight' => 60]]])->assertSessionHasNoErrors();
        $this->assertSame(['Reka Bentuk', 'Bina'], $project->milestones()->pluck('client_label')->all());
        $this->assertDatabaseHas('audit_logs', ['action' => 'MILESTONES_CHANGED']);

        $this->actingAs($admin, 'admin')->post(route('admin.projects.start', $project));
        $this->actingAs($admin, 'admin')->put(route('admin.projects.milestones.update', $project), ['milestones' => [['name' => 'X', 'client_label' => 'X', 'weight' => 100]]])->assertSessionHasErrors('milestones');
        $this->assertSame(2, $project->milestones()->count());
    }

    public function test_admin_reschedule_with_capacity_override_and_client_notified(): void
    {
        SchedulingSetting::current()->update(['max_active_projects' => 1]);
        [$client, $project] = $this->confirmedProject();
        [, $other] = $this->confirmedProject(startWeeksAhead: 4);
        $admin = $this->admin();
        $busy = SlotHold::query()->where('quotation_id', $other->quotation_id)->value('start_date')->toDateString();

        $this->actingAs($admin, 'admin')->post(route('admin.projects.reschedule', $project), ['start_date' => now()->next('Tuesday')->toDateString(), 'weeks' => 3, 'reason' => 'x'])->assertSessionHasErrors('start_date');
        $this->actingAs($admin, 'admin')->post(route('admin.projects.reschedule', $project), ['start_date' => $busy, 'weeks' => 3, 'reason' => 'Pelanggan minta tangguh'])->assertSessionHasErrors('override');
        $this->actingAs($admin, 'admin')->post(route('admin.projects.reschedule', $project), ['start_date' => $busy, 'weeks' => 5, 'reason' => 'Pelanggan minta tangguh', 'override' => '1'])->assertSessionHasNoErrors();

        $hold = SlotHold::query()->where('quotation_id', $project->quotation_id)->sole();
        $this->assertSame($busy, $hold->start_date->toDateString());
        $this->assertSame(5, $hold->weeks);
        $this->assertFalse($hold->conflict);
        $this->assertSame($busy, $project->fresh()->planned_start_date->toDateString());
        $this->assertDatabaseHas('audit_logs', ['action' => 'SLOT_RESCHEDULED', 'reason' => 'Pelanggan minta tangguh']);
        $this->assertDatabaseHas('portal_notifications', ['recipient_id' => $client->id, 'title' => 'Jadual projek dikemas kini']);
    }

    public function test_change_request_additional_work_flow(): void
    {
        [$client, $project] = $this->confirmedProject(withAffiliate: true);
        $admin = $this->admin();
        $intruder = $this->portalReadyClient();

        $this->actingAs($intruder, 'client')->post(route('client.projects.changes.store', $project), ['title' => 'x', 'description' => 'y'])->assertNotFound();
        $this->actingAs($client, 'client')->post(route('client.projects.changes.store', $project), ['title' => 'Tambah blog', 'description' => 'Seksyen blog dengan 3 kategori'])->assertRedirect();
        $cr = ChangeRequest::query()->sole();
        $this->assertStringStartsWith('NAT-CR-', $cr->number);

        $this->actingAs($admin, 'admin')->post(route('admin.change-requests.assess', $cr), ['decision' => 'ADDITIONAL_WORK'])->assertSessionHasErrors('amount');
        $this->actingAs($admin, 'admin')->post(route('admin.change-requests.assess', $cr), ['decision' => 'ADDITIONAL_WORK', 'amount' => '1200', 'extra_weeks' => 2, 'admin_note' => 'Termasuk 3 templat'])->assertSessionHasNoErrors();
        $this->actingAs($admin, 'admin')->post(route('admin.change-requests.assess', $cr), ['decision' => 'IN_SCOPE'])->assertSessionHasErrors('change');
        $this->assertSame(ChangeRequest::QUOTED, $cr->fresh()->status);
        $this->assertSame('6000.00', $project->quotation->fresh()->total_amount);

        $this->actingAs($client, 'client')->get(route('client.projects.show', $project))->assertSee('RM 1,200.00')->assertSee('Luluskan');
        $this->actingAs($intruder, 'client')->post(route('client.changes.decide', $cr), ['decision' => 'approve', 'agree' => '1'])->assertNotFound();
        $this->actingAs($client, 'client')->post(route('client.changes.decide', $cr), ['decision' => 'approve'])->assertSessionHasErrors('agree');
        $response = $this->actingAs($client, 'client')->post(route('client.changes.decide', $cr), ['decision' => 'approve', 'agree' => '1']);
        $this->actingAs($client, 'client')->post(route('client.changes.decide', $cr), ['decision' => 'approve', 'agree' => '1']);

        $invoice = Invoice::query()->where('type', 'ADDITIONAL_CHARGE')->sole();
        $response->assertRedirect(route('client.billing.invoice', $invoice));
        $this->assertSame('1200.00', $invoice->total);
        $this->assertSame(['ChangeRequest', $cr->id], [$invoice->source_type, $invoice->source_id]);
        $this->assertSame($invoice->id, $cr->fresh()->invoice_id);
        $this->assertSame(5, SlotHold::query()->sole()->weeks);
        $this->assertNotNull(AffiliateCommission::query()->where('invoice_id', $invoice->id)->first());

        // Pengesahan bayaran projek memerlukan invois kerja tambahan juga dibayar
        foreach (Invoice::query()->where('source_type', 'Quotation')->get() as $inv) {
            $inv->forceFill(['status' => 'PAID', 'amount_paid' => $inv->total])->save();
        }
        Invoice::query()->create(['number' => 'NAT-INV-T-FINAL', 'type' => 'FINAL_PAYMENT', 'status' => 'PAID', 'customer_user_id' => $client->id, 'source_type' => 'Quotation', 'source_id' => $project->quotation_id,
            'seller_snapshot' => [], 'customer_snapshot' => [], 'items_snapshot' => [], 'subtotal' => '3000', 'tax_amount' => '0', 'total' => '3000', 'amount_paid' => '3000', 'issued_at' => now()]);
        $service = app(ProjectPaymentService::class);
        $this->assertFalse($service->summary($project->quotation->fresh())['can_confirm']);
        $invoice->forceFill(['status' => 'PAID', 'amount_paid' => '1200.00'])->save();
        $this->assertTrue($service->summary($project->quotation->fresh())['can_confirm']);
        $service->confirmCompleted($admin, $project->quotation->fresh());
        $this->assertSame('RELEASED', AffiliateCommission::query()->where('invoice_id', $invoice->id)->value('status'));
    }

    public function test_change_request_in_scope_and_decline(): void
    {
        [$client, $project] = $this->confirmedProject();
        $admin = $this->admin();
        $this->actingAs($client, 'client')->post(route('client.projects.changes.store', $project), ['title' => 'Tukar warna', 'description' => 'Biru gelap']);
        $this->actingAs($client, 'client')->post(route('client.projects.changes.store', $project), ['title' => 'Tambah app', 'description' => 'Aplikasi mudah alih']);
        [$a, $b] = ChangeRequest::query()->orderBy('id')->get();

        $this->actingAs($admin, 'admin')->post(route('admin.change-requests.assess', $a), ['decision' => 'IN_SCOPE', 'admin_note' => 'Termasuk revision'])->assertSessionHasNoErrors();
        $this->actingAs($admin, 'admin')->post(route('admin.change-requests.assess', $b), ['decision' => 'DECLINE'])->assertSessionHasErrors('admin_note');
        $this->actingAs($admin, 'admin')->post(route('admin.change-requests.assess', $b), ['decision' => 'DECLINE', 'admin_note' => 'Di luar servis kami'])->assertSessionHasNoErrors();

        $this->assertSame(ChangeRequest::IN_SCOPE, $a->fresh()->status);
        $this->assertSame(ChangeRequest::DECLINED, $b->fresh()->status);
        $this->assertSame(0, Invoice::query()->where('type', 'ADDITIONAL_CHARGE')->count());
        $this->actingAs($client, 'client')->post(route('client.changes.decide', $a), ['decision' => 'approve', 'agree' => '1'])->assertSessionHasErrors('change');
        $this->actingAs($admin, 'admin')->get(route('admin.change-requests.index', ['status' => 'ALL']))->assertOk()->assertSee($a->number);
    }

    public function test_notifications_are_emailed_via_resend_when_enabled_and_failures_do_not_break_flow(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin, 'admin')->put(route('admin.communication.email.update'), ['resend_api_key' => 'bad', 'from_email' => 'noreply@natnetwork.net'])->assertSessionHasErrors('resend_api_key');
        $this->actingAs($admin, 'admin')->put(route('admin.communication.email.update'), ['from_email' => 'noreply@natnetwork.net', 'email_enabled' => '1'])->assertSessionHasErrors('resend_api_key');
        $this->actingAs($admin, 'admin')->put(route('admin.communication.email.update'), ['resend_api_key' => 're_test_123456', 'from_email' => 'noreply@natnetwork.net', 'from_name' => 'NatNetwork', 'email_enabled' => '1'])->assertSessionHasNoErrors();
        $this->assertStringNotContainsString('re_test_123456', (string) DB::table('communication_settings')->value('resend_api_key'));
        $this->assertTrue(CommunicationSetting::current()->resendReady());
        $this->actingAs($admin, 'admin')->get(route('admin.communication.email'))->assertOk()->assertDontSee('re_test_123456')->assertSee('••••3456');

        Http::fake(['api.resend.com/emails' => Http::sequence()->push(['id' => 'em_1'])->push(['message' => 'Invalid from'], 422)->push(['id' => 'em_3'])->push(['id' => 'em_4'])->push(['id' => 'em_5'])->push(['id' => 'em_6'])]);

        $this->actingAs($admin, 'admin')->post(route('admin.communication.email.test'))->assertSessionHasNoErrors();
        $this->actingAs($admin, 'admin')->post(route('admin.communication.email.test'))->assertSessionHasErrors('test');

        [$client, $project] = $this->confirmedProject();
        $this->actingAs($admin, 'admin')->post(route('admin.projects.start', $project))->assertSessionHasNoErrors();

        Http::assertSent(fn ($r) => $r->url() === 'https://api.resend.com/emails' && $r->hasHeader('Authorization', 'Bearer re_test_123456')
            && $r['to'] === [$client->email] && $r['from'] === 'NatNetwork <noreply@natnetwork.net>' && str_contains($r['subject'], $project->number));
        $this->assertSame(1, EmailLog::query()->where('status', 'FAILED')->count());
        $this->assertTrue(EmailLog::query()->where('recipient', $client->email)->where('status', 'SENT')->where('channel', 'RESEND')->exists());

        // OTP juga melalui Resend
        $this->post(route('client.otp.send'), ['email' => $client->email]);
        Http::assertSent(fn ($r) => $r['subject'] === 'Kod log masuk NatNetwork' && $r['to'] === [$client->email]);
    }

    public function test_email_falls_back_to_mailer_when_resend_disabled(): void
    {
        Http::fake();
        [$client, $project] = $this->confirmedProject();
        $this->actingAs($this->admin(), 'admin')->post(route('admin.projects.start', $project));

        Http::assertNothingSent();
        $this->assertTrue(EmailLog::query()->where('recipient', $client->email)->where('channel', 'MAILER')->where('status', 'SENT')->exists());
    }

    private function payDeposit(Project $project): Invoice
    {
        $deposit = Invoice::query()->where('source_id', $project->quotation_id)->where('type', 'DEPOSIT')->sole();
        $deposit->forceFill(['status' => 'PAID', 'amount_paid' => $deposit->total, 'paid_at' => now()])->save();
        Payment::query()->create(['invoice_id' => $deposit->id, 'gateway' => 'BILLPLZ', 'gateway_mode' => 'PRODUCTION', 'amount_cents' => 300000, 'paid_amount_cents' => 300000, 'status' => Payment::STATUS_PAID, 'paid_at' => now()]);

        return $deposit->fresh();
    }

    /** @return array{0: User, 1: Project} */
    private function confirmedProject(bool $withAffiliate = false, int $startWeeksAhead = 0): array
    {
        BillingSetting::current()->forceFill(['active_mode' => 'PRODUCTION', 'production_api_key' => 'k', 'production_collection_id' => 'c', 'production_x_signature_key' => 's'])->save();
        $client = User::factory()->create();
        if ($withAffiliate) {
            $aff = Affiliate::query()->create(['name' => 'Aff', 'email' => 'aff'.$client->id.'@example.test', 'phone' => '012', 'username' => 'aff'.$client->id]);
            AffiliateClientLink::query()->create(['affiliate_id' => $aff->id, 'customer_user_id' => $client->id, 'linked_at' => now()]);
        }
        $builder = BuilderSession::query()->create(['entry_path' => 'GUIDED', 'user_id' => $client->id, 'contact_name' => $client->name, 'contact_email' => $client->email, 'contact_phone' => '012']);
        $request = ProjectRequest::query()->create(['customer_user_id' => $client->id, 'builder_session_id' => $builder->id, 'status' => 'DRAFT', 'requirement_snapshot' => []]);
        $spec = MasterSpecification::query()->create(['project_request_id' => $request->id, 'version' => 1, 'status' => 'APPROVED', 'requirement_snapshot' => [], 'specification_snapshot' => ['project_summary' => []], 'approved_at' => now()]);
        $quotation = Quotation::query()->create([
            'project_request_id' => $request->id, 'master_specification_id' => $spec->id, 'status' => Quotation::STATUS_ACCEPTED,
            'number' => 'NAT-QT-T-'.$request->id, 'total_amount' => '6000.00', 'estimated_weeks' => 3, 'accepted_at' => now(),
            'price_snapshot' => ['selected_package' => ['name' => 'Website'], 'project_template' => 'website'], 'terms_snapshot' => config('sales_terms'),
        ]);
        $hold = app(SchedulingService::class)->hold($quotation, now()->next('Monday')->addWeeks($startWeeksAhead)->toDateString());
        app(SchedulingService::class)->reserveForQuotation($quotation);
        $order = Order::query()->create(['number' => 'NAT-ORD-T-'.$quotation->id, 'quotation_id' => $quotation->id, 'customer_user_id' => $client->id, 'slot_hold_id' => $hold->id, 'status' => Order::CONFIRMED, 'confirmed_at' => now()]);

        return [$client, app(ProjectService::class)->createFromOrder($order)];
    }

    private function admin(): Admin
    {
        $admin = Admin::query()->firstOrCreate(['email' => 'admin@example.test'], ['name' => 'Nat', 'password' => 'password-yang-panjang']);
        $admin->forceFill(['two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'two_factor_confirmed_at' => now()])->save();

        return $admin;
    }
}
