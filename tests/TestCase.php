<?php

namespace Tests;

use App\Engines\Sales\Models\BuilderSession;
use App\Engines\Sales\Models\MasterSpecification;
use App\Engines\Sales\Models\Order;
use App\Engines\Sales\Models\ProjectRequest;
use App\Engines\Sales\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** Pelanggan yang telah membuat bayaran pertama (Order wujud) — Portal Client penuh dibuka. */
    protected function portalReadyClient(?User $client = null): User
    {
        $client ??= User::factory()->create();
        $builder = BuilderSession::query()->create(['entry_path' => 'GUIDED', 'user_id' => $client->id]);
        $request = ProjectRequest::query()->create(['customer_user_id' => $client->id, 'builder_session_id' => $builder->id, 'status' => 'DRAFT', 'requirement_snapshot' => []]);
        $spec = MasterSpecification::query()->create(['project_request_id' => $request->id, 'version' => 1, 'status' => 'APPROVED', 'requirement_snapshot' => [], 'specification_snapshot' => ['project_summary' => []], 'approved_at' => now()]);
        $quotation = Quotation::query()->create(['project_request_id' => $request->id, 'master_specification_id' => $spec->id, 'status' => Quotation::STATUS_ACCEPTED, 'number' => 'NAT-QT-PR-'.$request->id, 'total_amount' => '1000.00', 'accepted_at' => now(), 'price_snapshot' => ['items' => []], 'terms_snapshot' => []]);
        Order::query()->create(['number' => 'NAT-ORD-PR-'.$request->id, 'quotation_id' => $quotation->id, 'customer_user_id' => $client->id, 'status' => Order::CONFIRMED, 'confirmed_at' => now()]);

        return $client;
    }
}
