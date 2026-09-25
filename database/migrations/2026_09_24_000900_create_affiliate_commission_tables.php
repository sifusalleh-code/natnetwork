<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Setiap perubahan kadar oleh admin = versi baharu. Versi lama kekal (komisyen lama merujuknya).
        Schema::create('affiliate_rate_versions', function (Blueprint $table) {
            $table->id();
            $table->json('tiers');
            $table->unsignedBigInteger('created_by_admin_id')->nullable();
            $table->timestamp('effective_from');
            $table->timestamps();
        });

        DB::table('affiliate_rate_versions')->insert([
            'tiers' => json_encode([
                ['up_to' => '500.00', 'rate' => '10.00'],
                ['up_to' => '2000.00', 'rate' => '8.00'],
                ['up_to' => '5000.00', 'rate' => '6.00'],
                ['up_to' => '10000.00', 'rate' => '4.00'],
                ['up_to' => null, 'rate' => '3.00'],
            ]),
            'effective_from' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('invoice_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('affiliate_rate_version_id')->constrained()->restrictOnDelete();
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('cumulative_before', 12, 2);
            $table->decimal('cumulative_after', 12, 2);
            $table->decimal('amount', 12, 2);
            $table->json('breakdown');
            $table->string('status', 20);
            $table->timestamp('released_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['affiliate_id', 'status']);
            $table->index('customer_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_commissions');
        Schema::dropIfExists('affiliate_rate_versions');
    }
};
