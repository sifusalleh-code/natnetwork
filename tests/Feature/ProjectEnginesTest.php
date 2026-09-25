<?php

namespace Tests\Feature;

use App\Engines\Billing\Models\Invoice;
use App\Engines\Communication\Models\PortalNotification;
use App\Engines\Communication\Models\SupportTicket;
use App\Engines\Identity\Models\Admin;
use App\Engines\Project\Models\Project;
use App\Engines\Project\Services\ProjectService;
use App\Engines\ProjectContent\Models\ProjectContentItem;
use App\Engines\ProjectContent\Models\ProjectFile;
use App\Engines\Sales\Models\BuilderSession;
use App\Engines\Sales\Models\MasterSpecification;
use App\Engines\Sales\Models\Order;
use App\Engines\Sales\Models\ProjectRequest;
use App\Engines\Sales\Models\Quotation;
use App\Engines\Scheduling\Models\SchedulingSetting;
use App\Engines\Scheduling\Models\SlotHold;
use App\Engines\Scheduling\Services\SchedulingService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectEnginesTest extends TestCase
{
    use RefreshDatabase;

    public function test_slot_capacity_blocks_full_weeks_and_expired_hold_blocks_deposit_payment(): void
    {
        SchedulingSetting::current()->update(['max_active_projects' => 1]);
        [$a, $qa] = $this->acceptedQuotation();
        [$b, $qb] = $this->acceptedQuotation();
        $monday = now()->next('Monday')->toDateString();

        $this->actingAs($a, 'client')->post(route('client.quotations.slot.hold', $qa), ['start_date' => $monday, 'plan' => 'DEPOSIT'])->assertSessionHasNoErrors();
        $this->actingAs($b, 'client')->post(route('client.quotations.slot.hold', $qb), ['start_date' => $monday, 'plan' => 'DEPOSIT'])->assertSessionHasErrors('slot');
        $this->actingAs($b, 'client')->get(route('client.quotations.slot', $qb))->assertOk()->assertSee('Penuh');
        // Bukan Isnin / di luar senarai ditolak
        $this->actingAs($b, 'client')->post(route('client.quotations.slot.hold', $qb), ['start_date' => now()->next('Tuesday')->toDateString(), 'plan' => 'DEPOSIT'])->assertSessionHasErrors('slot');

        // Hold tamat → kapasiti dibebaskan, bayaran deposit A disekat sehingga slot dipilih semula
        SlotHold::query()->update(['expires_at' => now()->subMinute()]);
        $deposit = Invoice::query()->where('source_id', $qa->id)->sole();
        $this->actingAs($a, 'client')->post(route('client.billing.pay', $deposit))->assertRedirect(route('client.quotations.slot', $qa))->assertSessionHasErrors('slot');
        $this->actingAs($b, 'client')->post(route('client.quotations.slot.hold', $qb), ['start_date' => $monday, 'plan' => 'DEPOSIT'])->assertSessionHasNoErrors();
        $this->assertSame('EXPIRED', SlotHold::query()->where('quotation_id', $qa->id)->value('status'));

        // Pilih semula tidak mencipta invois deposit kedua
        $this->actingAs($a, 'client')->post(route('client.quotations.slot.hold', $qa), ['start_date' => now()->next('Monday')->addWeeks(4)->toDateString(), 'plan' => 'DEPOSIT'])->assertSessionHasNoErrors();
        $this->assertSame(1, Invoice::query()->where('source_id', $qa->id)->where('type', 'DEPOSIT')->count());
    }

    public function test_content_review_file_visibility_and_ownership(): void
    {
        Storage::fake('local');
        [$client, $project] = $this->confirmedProject();
        $admin = $this->admin();
        $intruder = $this->portalReadyClient();
        $logo = $project->contentItems()->where('kind', 'FILE')->first();
        $info = $project->contentItems()->where('kind', 'INFO')->first();

        $this->actingAs($client, 'client')->post(route('client.content.file', $logo), ['file' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php')])->assertSessionHasErrors('file');
        $this->actingAs($intruder, 'client')->post(route('client.content.info', $info), ['info_text' => 'x'])->assertNotFound();
        $this->actingAs($client, 'client')->post(route('client.content.file', $logo), ['file' => UploadedFile::fake()->image('logo.png')])->assertSessionHasNoErrors();
        $this->actingAs($client, 'client')->post(route('client.content.info', $info), ['info_text' => 'Syarikat ABC'])->assertSessionHasNoErrors();
        $this->assertSame(ProjectContentItem::SUBMITTED, $logo->fresh()->status);

        // Perlu kemas kini wajib ada sebab
        $this->actingAs($admin, 'admin')->post(route('admin.projects.content.review', $logo), ['decision' => 'update'])->assertSessionHasErrors('reason');
        $this->actingAs($admin, 'admin')->post(route('admin.projects.content.review', $logo), ['decision' => 'update', 'reason' => 'Resolusi rendah'])->assertSessionHasNoErrors();
        $this->actingAs($admin, 'admin')->post(route('admin.projects.content.review', $info), ['decision' => 'accept'])->assertSessionHasNoErrors();
        $this->assertSame(ProjectContentItem::NEEDS_UPDATE, $logo->fresh()->status);
        $this->actingAs($client, 'client')->get(route('client.projects.show', $project))->assertSee('Resolusi rendah');
        $this->actingAs($client, 'client')->get(route('client.dashboard'))->assertSee('Kemas kini: '.$logo->title);

        // Fail INTERNAL tidak kelihatan / tidak boleh dimuat turun oleh pelanggan
        $this->actingAs($admin, 'admin')->post(route('admin.projects.files.upload', $project), ['file' => UploadedFile::fake()->create('nota.pdf', 5, 'application/pdf'), 'visibility' => 'INTERNAL'])->assertSessionHasNoErrors();
        $this->actingAs($admin, 'admin')->post(route('admin.projects.files.upload', $project), ['file' => UploadedFile::fake()->create('serahan.pdf', 5, 'application/pdf'), 'visibility' => 'CLIENT', 'is_deliverable' => '1'])->assertSessionHasNoErrors();
        $internal = ProjectFile::query()->where('visibility', 'INTERNAL')->sole();
        $deliverable = ProjectFile::query()->where('original_name', 'serahan.pdf')->sole();
        $this->assertTrue($deliverable->is_deliverable);
        $this->actingAs($client, 'client')->get(route('client.files'))->assertSee('serahan.pdf')->assertSee('logo.png')->assertDontSee('nota.pdf');
        $this->actingAs($client, 'client')->get(route('client.files.download', $internal))->assertNotFound();
        $this->actingAs($client, 'client')->get(route('client.files.download', $deliverable))->assertOk();
        $this->actingAs($intruder, 'client')->get(route('client.files.download', $deliverable))->assertNotFound();
        $this->actingAs($intruder, 'client')->get(route('client.projects.show', $project))->assertNotFound();
        $this->actingAs($admin, 'admin')->get(route('admin.projects.show', $project))->assertOk()->assertSee('nota.pdf');
    }

    public function test_status_transitions_require_reason_and_reject_invalid(): void
    {
        [, $project] = $this->confirmedProject();
        $admin = $this->admin();

        $this->actingAs($admin, 'admin')->post(route('admin.projects.transition', $project), ['to' => 'CLIENT_REVIEW'])->assertSessionHasErrors('status');
        $this->actingAs($admin, 'admin')->post(route('admin.projects.start', $project));
        $this->actingAs($admin, 'admin')->post(route('admin.projects.transition', $project), ['to' => 'WAITING_FOR_CLIENT'])->assertSessionHasErrors('reason');
        $this->actingAs($admin, 'admin')->post(route('admin.projects.transition', $project), ['to' => 'WAITING_FOR_CLIENT', 'reason' => 'Menunggu logo'])->assertSessionHasNoErrors();
        $this->assertSame(Project::WAITING_FOR_CLIENT, $project->fresh()->status);
        $this->assertDatabaseHas('project_status_logs', ['project_id' => $project->id, 'to_status' => 'WAITING_FOR_CLIENT', 'reason' => 'Menunggu logo']);
        $this->assertDatabaseHas('portal_notifications', ['recipient_id' => $project->customer_user_id, 'category' => 'PROJECT']);
        $this->actingAs($admin, 'admin')->get(route('admin.projects.queue'))->assertOk()->assertSee($project->quotation->number);
    }

    public function test_support_ticket_flow_and_notifications_are_private(): void
    {
        [$client, $project] = $this->confirmedProject();
        $admin = $this->admin();
        $intruder = $this->portalReadyClient();

        $this->actingAs($client, 'client')->post(route('client.support.store'), ['subject' => 'Bantuan', 'body' => 'Tolong', 'project_id' => $project->id])->assertRedirect();
        $ticket = SupportTicket::query()->sole();
        $this->assertStringStartsWith('NAT-SUP-', $ticket->number);
        $this->actingAs($intruder, 'client')->post(route('client.support.store'), ['subject' => 'x', 'body' => 'y', 'project_id' => $project->id])->assertNotFound();
        $this->actingAs($intruder, 'client')->get(route('client.support.show', $ticket))->assertNotFound();
        $this->actingAs($intruder, 'client')->post(route('client.support.reply', $ticket), ['body' => 'x'])->assertNotFound();

        $this->actingAs($admin, 'admin')->post(route('admin.support.reply', $ticket), ['body' => 'Baik, kami semak', 'close' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(SupportTicket::CLOSED, $ticket->fresh()->status);
        $this->actingAs($client, 'client')->get(route('client.support.show', $ticket))->assertOk()->assertSee('Baik, kami semak');
        $this->actingAs($client, 'client')->post(route('client.support.reply', $ticket), ['body' => 'lagi'])->assertSessionHasErrors('body');

        $n = PortalNotification::query()->where('category', 'SUPPORT')->sole();
        $this->actingAs($client, 'client')->get(route('client.dashboard'))->assertSee('prt-badge warn', false);
        $this->actingAs($intruder, 'client')->get(route('client.notifications.open', $n))->assertNotFound();
        $this->actingAs($client, 'client')->get(route('client.notifications.open', $n))->assertRedirect(route('client.support.show', $ticket));
        $this->assertNotNull($n->fresh()->read_at);
        $this->actingAs($client, 'client')->post(route('client.notifications.read-all'))->assertRedirect();
        $this->assertSame(0, PortalNotification::query()->where('recipient_id', $client->id)->whereNull('read_at')->count());
    }

    public function test_home_page_has_login_link(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('Log Masuk')->assertSee(route('client.login'), false);
    }

    /** @return array{0: User, 1: Quotation} */
    private function acceptedQuotation(): array
    {
        $client = User::factory()->create();
        $builder = BuilderSession::query()->create(['entry_path' => 'GUIDED', 'user_id' => $client->id, 'contact_name' => $client->name, 'contact_email' => $client->email, 'contact_phone' => '012']);
        $request = ProjectRequest::query()->create(['customer_user_id' => $client->id, 'builder_session_id' => $builder->id, 'status' => 'DRAFT', 'requirement_snapshot' => []]);
        $spec = MasterSpecification::query()->create(['project_request_id' => $request->id, 'version' => 1, 'status' => 'APPROVED', 'requirement_snapshot' => [], 'specification_snapshot' => ['project_summary' => []], 'approved_at' => now()]);
        $quotation = Quotation::query()->create([
            'project_request_id' => $request->id, 'master_specification_id' => $spec->id, 'status' => Quotation::STATUS_ACCEPTED,
            'number' => 'NAT-QT-T-'.$request->id, 'total_amount' => '6000.00', 'estimated_weeks' => 3, 'accepted_at' => now(),
            'price_snapshot' => ['selected_package' => ['name' => 'Website'], 'project_template' => 'website', 'items' => [['description' => 'Website', 'quantity' => 1, 'unit_price' => '6000.00', 'line_total' => '6000.00']]],
            'terms_snapshot' => config('sales_terms'),
        ]);

        return [$client, $quotation];
    }

    /** @return array{0: User, 1: Project} */
    private function confirmedProject(): array
    {
        [$client, $quotation] = $this->acceptedQuotation();
        $hold = app(SchedulingService::class)->hold($quotation, now()->next('Monday')->toDateString());
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
