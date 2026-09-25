<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseStatusCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_phase_status_reports_migrations_current_and_engines_present(): void
    {
        $this->artisan('phase:status')
            ->expectsOutputToContain('Terkini')
            ->expectsOutputToContain('Semua 13 wujud')
            ->expectsOutputToContain('Subscription Engine')
            ->assertSuccessful();
    }
}
