<?php

namespace Tests\Feature;

use App\Engines\Affiliate\Models\Affiliate;
use App\Engines\Affiliate\Models\AffiliateWithdrawal;
use App\Engines\Identity\Models\Admin;
use App\Engines\Partnership\Models\Partner;
use App\Engines\Partnership\Models\PartnerCapital;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminBulkAccountActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_suspend_activate_and_delete_clients(): void
    {
        $admin = $this->admin();
        $withHistory = $this->portalReadyClient();
        $empty = User::factory()->create();

        // Tanpa confirm → ditolak.
        $this->actingAs($admin, 'admin')->post(route('admin.clients.bulk'), ['ids' => [$empty->id], 'action' => 'suspend', 'reason' => 'ujian'])->assertSessionHasErrors('confirm');

        // Suspend tanpa sebab → ditolak.
        $this->actingAs($admin, 'admin')->post(route('admin.clients.bulk'), ['ids' => [$empty->id], 'action' => 'suspend', 'confirm' => '1'])->assertSessionHasErrors('reason');

        // Bulk suspend kedua-dua akaun.
        $this->actingAs($admin, 'admin')->post(route('admin.clients.bulk'), ['ids' => [$withHistory->id, $empty->id], 'action' => 'suspend', 'reason' => 'Aktiviti mencurigakan', 'confirm' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue($withHistory->fresh()->isSuspended());
        $this->assertTrue($empty->fresh()->isSuspended());
        $this->assertDatabaseHas('audit_logs', ['action' => 'ACCOUNT_SUSPENDED']);

        // Akaun tergantung tidak boleh log masuk — middleware log keluar & redirect dengan ralat.
        $this->actingAs($withHistory->fresh(), 'client')->get(route('client.dashboard'))->assertRedirect(route('client.login'))->assertSessionHasErrors('email');
        $this->assertFalse(auth('client')->check());

        // Bulk activate.
        $this->actingAs($admin, 'admin')->post(route('admin.clients.bulk'), ['ids' => [$withHistory->id], 'action' => 'activate', 'confirm' => '1'])->assertSessionHasNoErrors();
        $this->assertFalse($withHistory->fresh()->isSuspended());
        $this->assertDatabaseHas('audit_logs', ['action' => 'ACCOUNT_ACTIVATED']);

        // Bulk delete: akaun dengan sejarah (Order) dilangkau; akaun kosong dipadam.
        $response = $this->actingAs($admin, 'admin')->post(route('admin.clients.bulk'), ['ids' => [$withHistory->id, $empty->id], 'action' => 'delete', 'confirm' => '1']);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['id' => $withHistory->id]);
        $this->assertDatabaseMissing('users', ['id' => $empty->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'ACCOUNT_DELETED']);
    }

    public function test_bulk_suspend_and_delete_affiliates(): void
    {
        $admin = $this->admin();
        $affiliate = Affiliate::query()->create(['name' => 'Aff Satu', 'email' => 'aff1@example.test', 'phone' => '011']);

        $this->actingAs($admin, 'admin')->post(route('admin.affiliates.bulk'), ['ids' => [$affiliate->id], 'action' => 'suspend', 'reason' => 'Langgar terma', 'confirm' => '1'])->assertSessionHasNoErrors();
        $this->assertTrue($affiliate->fresh()->isSuspended());

        $this->actingAs($affiliate->fresh(), 'affiliate')->get(route('affiliate.dashboard'))->assertRedirect(route('affiliate.login'));
        $this->assertFalse(auth('affiliate')->check());

        $this->actingAs($admin, 'admin')->post(route('admin.affiliates.bulk'), ['ids' => [$affiliate->id], 'action' => 'delete', 'confirm' => '1'])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('affiliates', ['id' => $affiliate->id]);
    }

    public function test_bulk_partner_approve_reject_suspend_and_delete_skips_with_capital(): void
    {
        $admin = $this->admin();
        $pending = Partner::query()->create(['name' => 'P1', 'email' => 'p1@example.test', 'phone' => '1', 'id_type' => 'IC', 'id_number' => '1', 'status' => Partner::PENDING_REVIEW]);
        $funded = Partner::query()->create(['name' => 'P2', 'email' => 'p2@example.test', 'phone' => '2', 'id_type' => 'IC', 'id_number' => '2', 'status' => Partner::APPROVED]);
        PartnerCapital::query()->create(['number' => 'NAT-PTC-X1', 'partner_id' => $funded->id, 'amount' => '5000', 'status' => PartnerCapital::ACTIVE, 'terms_snapshot' => [], 'acceptance_metadata' => [], 'activated_at' => now()]);

        // Reject tanpa sebab → dilangkau (service menolak, dikira sebagai skipped, bukan error 500).
        $this->actingAs($admin, 'admin')->post(route('admin.partners.bulk'), ['ids' => [$pending->id], 'action' => 'reject', 'confirm' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(Partner::PENDING_REVIEW, $pending->fresh()->status);

        // Approve pukal.
        $this->actingAs($admin, 'admin')->post(route('admin.partners.bulk'), ['ids' => [$pending->id], 'action' => 'approve', 'confirm' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(Partner::APPROVED, $pending->fresh()->status);

        // Suspend pukal kedua-dua.
        $this->actingAs($admin, 'admin')->post(route('admin.partners.bulk'), ['ids' => [$pending->id, $funded->id], 'action' => 'suspend', 'reason' => 'Semakan', 'confirm' => '1'])->assertSessionHasNoErrors();
        $this->assertSame(Partner::SUSPENDED, $pending->fresh()->status);
        $this->assertSame(Partner::SUSPENDED, $funded->fresh()->status);
        $this->actingAs($funded->fresh(), 'partner')->get(route('partner.dashboard'))->assertRedirect(route('partner.login'));

        // Delete: partner tanpa modal dipadam; partner dengan modal aktif dilangkau (restrictOnDelete).
        $this->actingAs($admin, 'admin')->post(route('admin.partners.bulk'), ['ids' => [$pending->id, $funded->id], 'action' => 'delete', 'confirm' => '1'])->assertSessionHasNoErrors();
        $this->assertDatabaseMissing('partners', ['id' => $pending->id]);
        $this->assertDatabaseHas('partners', ['id' => $funded->id]);
    }

    public function test_bulk_reject_withdrawals_returns_balance_and_only_requested_ones(): void
    {
        $admin = $this->admin();
        $affiliate = Affiliate::query()->create(['name' => 'Aff', 'email' => 'affw@example.test', 'phone' => '011']);
        $w1 = AffiliateWithdrawal::query()->create(['affiliate_id' => $affiliate->id, 'amount' => '100.00', 'status' => AffiliateWithdrawal::STATUS_REQUESTED, 'bank_name' => 'Bank', 'bank_account_number' => '123', 'bank_account_last4' => '0123', 'account_holder' => 'Aff', 'week_shares' => 0, 'week_unique_clicks' => 0, 'requested_at' => now()]);
        $w2 = AffiliateWithdrawal::query()->create(['affiliate_id' => $affiliate->id, 'amount' => '50.00', 'status' => AffiliateWithdrawal::STATUS_PAID, 'bank_name' => 'Bank', 'bank_account_number' => '123', 'bank_account_last4' => '0123', 'account_holder' => 'Aff', 'week_shares' => 0, 'week_unique_clicks' => 0, 'requested_at' => now(), 'processed_at' => now()]);

        $this->actingAs($admin, 'admin')->post(route('admin.affiliate.withdrawals.bulk-reject'), ['ids' => [$w1->id, $w2->id], 'reason' => 'Data tidak lengkap', 'confirm' => '1'])->assertSessionHasNoErrors();

        $this->assertSame(AffiliateWithdrawal::STATUS_REJECTED, $w1->fresh()->status);
        $this->assertSame(AffiliateWithdrawal::STATUS_PAID, $w2->fresh()->status); // sudah PAID — tidak disentuh
        $this->assertDatabaseHas('audit_logs', ['action' => 'AFFILIATE_WITHDRAWAL_REJECTED']);
    }

    public function test_guest_cannot_call_bulk_routes(): void
    {
        $this->post(route('admin.clients.bulk'), ['ids' => [1], 'action' => 'suspend', 'reason' => 'x', 'confirm' => '1'])->assertRedirect(route('admin.login'));
    }

    public function test_account_list_pages_render_with_bulk_toolbar_markup(): void
    {
        $admin = $this->admin();
        User::factory()->create();
        Affiliate::query()->create(['name' => 'Aff', 'email' => 'affr@example.test', 'phone' => '011']);
        Partner::query()->create(['name' => 'P', 'email' => 'pr@example.test', 'phone' => '1', 'id_type' => 'IC', 'id_number' => '1', 'status' => Partner::PENDING_REVIEW]);
        AffiliateWithdrawal::query()->create(['affiliate_id' => Affiliate::query()->first()->id, 'amount' => '10.00', 'status' => AffiliateWithdrawal::STATUS_REQUESTED, 'bank_name' => 'Bank', 'bank_account_number' => '1', 'bank_account_last4' => '0001', 'account_holder' => 'Aff', 'week_shares' => 0, 'week_unique_clicks' => 0, 'requested_at' => now()]);

        $this->actingAs($admin, 'admin')->get(route('admin.clients.index'))->assertOk()->assertSee('ids[]', false);
        $this->actingAs($admin, 'admin')->get(route('admin.affiliates.index'))->assertOk()->assertSee('ids[]', false);
        $this->actingAs($admin, 'admin')->get(route('admin.partners.index'))->assertOk()->assertSee('ids[]', false);
        $this->actingAs($admin, 'admin')->get(route('admin.affiliate.withdrawals'))->assertOk()->assertSee('ids[]', false);
    }

    private function admin(): Admin
    {
        $admin = Admin::query()->firstOrCreate(['email' => 'admin@example.test'], ['name' => 'Nat', 'password' => 'password-yang-panjang']);
        $admin->forceFill(['two_factor_secret' => 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ', 'two_factor_confirmed_at' => now()])->save();

        return $admin;
    }
}
