<?php

namespace Tests\Feature;

use App\Engines\Identity\Models\EmailOtpChallenge;
use App\Engines\Pricing\Models\Addon;
use App\Engines\Pricing\Models\ServicePackage;
use App\Engines\Sales\Models\BuilderAnswer;
use App\Engines\Sales\Models\BuilderQuestion;
use App\Engines\Sales\Models\BuilderSession;
use App\Engines\Sales\Models\MasterSpecification;
use App\Engines\Sales\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MasterSpecificationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_approved_specification_with_package_and_addons_generates_quotation_directly(): void
    {
        // Add-on ditawarkan ikut pakej (Business Website): Blog / News dan Booking, serta Additional Page dipilih terus.
        [$customer, $builder] = $this->verifiedBuilder('business-website', ['additional-page', 'blog-news', 'appointment-booking']);
        $this->actingAs($customer, 'client')->withSession(['natnetwork_builder_session_id' => $builder->id])
            ->post(route('specification.generate'))->assertRedirect(route('specification.show'));
        $specification = MasterSpecification::query()->firstOrFail();
        $this->assertSame(690000, $specification->specification_snapshot['cost_estimate']['total_cents']);
        $this->get(route('specification.show'))->assertSee('Kos projek')->assertSee('RM6,900.00')->assertSee('Add-on: Blog / News')->assertDontSee('Add-on: Gallery');

        $quotationUrl = fn () => route('client.quotations.show', Quotation::query()->firstOrFail());
        $this->post(route('specification.approve', $specification))->assertRedirect();
        $quotation = Quotation::query()->firstOrFail();
        $this->assertSame(Quotation::STATUS_SENT, $quotation->status);
        $this->assertSame('6900.00', $quotation->total_amount);
        $this->assertSame(['Business Website', 'Add-on: Additional page', 'Add-on: Blog / News', 'Add-on: Booking'], array_column($quotation->price_snapshot['items'], 'description'));
        $this->assertNotNull($quotation->valid_until);
        $this->assertSame(2, $quotation->estimated_weeks); // 5–10 hari
        $this->assertDatabaseHas('audit_logs', ['action' => 'QUOTATION_SENT', 'actor_type' => 'SYSTEM']);
        $this->get($quotationUrl())->assertOk()->assertSee('RM 6,900.00');
    }

    public function test_verified_customer_generates_and_approves_an_immutable_specification(): void
    {
        [$customer, $builder] = $this->verifiedBuilder('enterprise-complex');
        $this->actingAs($customer, 'client')->withSession(['natnetwork_builder_session_id' => $builder->id])
            ->post(route('specification.generate'))->assertRedirect(route('specification.show'));

        $specification = MasterSpecification::query()->firstOrFail();
        $this->assertSame(MasterSpecification::STATUS_DRAFT, $specification->status);
        $this->post(route('specification.approve', $specification))->assertRedirect(route('specification.show'));
        $this->assertDatabaseHas('master_specifications', ['id' => $specification->id, 'status' => MasterSpecification::STATUS_APPROVED, 'approved_by_user_id' => $customer->id]);
        $this->assertDatabaseHas('quotations', ['master_specification_id' => $specification->id, 'status' => Quotation::STATUS_REVIEW_REQUIRED]);
        $this->assertSame('DRAFT / OWNER REVIEW REQUIRED', Quotation::query()->firstOrFail()->terms_snapshot['approval_status']);
        $this->post(route('specification.approve', $specification))->assertSessionHasErrors('specification');
    }

    public function test_customer_cannot_approve_a_stale_draft_after_editing_builder_answers(): void
    {
        [$customer, $builder] = $this->verifiedBuilder();
        $this->actingAs($customer, 'client')->withSession(['natnetwork_builder_session_id' => $builder->id])
            ->post(route('specification.generate'));
        $specification = MasterSpecification::query()->firstOrFail();

        $this->travel(5)->seconds();
        $this->post(route('builder.save'), ['answers' => ['project_type' => 'website-development'], 'service_package_id' => $builder->service_package_id, 'addon_ids' => []])
            ->assertSessionHasNoErrors();

        $this->post(route('specification.approve', $specification))->assertSessionHasErrors('specification');
        $this->assertDatabaseHas('master_specifications', ['id' => $specification->id, 'status' => MasterSpecification::STATUS_DRAFT]);
    }

    public function test_returning_customer_login_restores_their_latest_builder(): void
    {
        [$customer, $builder] = $this->verifiedBuilder();
        EmailOtpChallenge::query()->create([
            'user_id' => $customer->id, 'email' => $customer->email, 'purpose' => EmailOtpChallenge::PURPOSE_CLIENT_LOGIN,
            'code_hash' => Hash::make('123456'), 'attempts' => 0, 'sent_at' => now(), 'expires_at' => now()->addMinutes(10),
        ]);

        $this->post(route('client.otp.verify'), ['email' => $customer->email, 'code' => '123456'])->assertRedirect(route('client.dashboard'));

        $this->assertSame($builder->id, session('natnetwork_builder_session_id'));
        $this->get(route('specification.show'))->assertOk();
    }

    public function test_customer_cannot_view_another_customers_specification(): void
    {
        [, $builder] = $this->verifiedBuilder();
        $other = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($other, 'client')->withSession(['natnetwork_builder_session_id' => $builder->id])
            ->get(route('specification.show'))->assertForbidden();
    }

    private function verifiedBuilder(string $package = 'landing-page', array $addons = []): array
    {
        $this->seed();
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER, 'email_verified_at' => now()]);
        $builder = BuilderSession::query()->create(['user_id' => $customer->id, 'entry_path' => 'GUIDED', 'email_verified_at' => now(),
            'service_package_id' => ServicePackage::query()->where('slug', $package)->value('id'),
            'addon_ids' => Addon::query()->whereIn('slug', $addons)->pluck('id')->all()]);
        $question = BuilderQuestion::query()->where('code', 'project_type')->firstOrFail();
        BuilderAnswer::query()->create(['builder_session_id' => $builder->id, 'builder_question_id' => $question->id, 'value' => ['website-development']]);

        return [$customer, $builder];
    }
}
