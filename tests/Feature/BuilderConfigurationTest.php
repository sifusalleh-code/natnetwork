<?php

namespace Tests\Feature;

use App\Engines\Sales\Models\BuilderSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuilderConfigurationTest extends TestCase
{
    use RefreshDatabase;

    public function test_builder_configuration_contains_the_locked_conditional_questions(): void
    {
        $this->seed();

        $this->assertDatabaseHas('builder_questions', ['code' => 'project_type', 'is_required' => true]);
        $this->assertDatabaseHas('builder_question_options', ['code' => 'e-commerce', 'label' => 'E-Commerce']);
        $this->assertDatabaseHas('builder_questions', ['code' => 'customer_login_method']);
        $this->assertDatabaseHas('builder_questions', ['code' => 'ecommerce_product_quantity']);
        $this->assertDatabaseHas('builder_questions', ['code' => 'ai_usage']);
        $this->assertDatabaseHas('builder_questions', ['code' => 'content_hosting']);
        $this->assertDatabaseHas('builder_question_options', ['code' => 'need-help', 'label' => 'Perlukan bantuan']);
        $this->assertDatabaseHas('builder_questions', ['code' => 'additional_customer_requirement']);
        foreach (['biru', 'hijau', 'ungu', 'merah', 'oren', 'kelabu', 'coklat', 'lain-lain'] as $colour) {
            $this->assertDatabaseHas('builder_question_options', ['code' => $colour]);
        }
        $this->assertDatabaseMissing('builder_question_options', ['code' => 'follow-branding']);
        $this->assertDatabaseHas('builder_questions', ['code' => 'domain_name', 'question_type' => 'text']);
        $this->assertDatabaseHas('builder_questions', ['code' => 'reference_available']);
        $this->assertDatabaseHas('builder_question_options', ['code' => 'under-1000', 'label' => 'Bawah RM1,000']);
    }

    public function test_builder_session_receives_a_resume_token(): void
    {
        $session = BuilderSession::query()->create(['entry_path' => 'GUIDED']);

        $this->assertNotEmpty($session->resume_token);
        $this->assertDatabaseHas('builder_sessions', ['id' => $session->id, 'entry_path' => 'GUIDED']);
    }
}
