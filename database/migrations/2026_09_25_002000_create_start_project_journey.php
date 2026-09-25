<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sales: kemajuan Start Project disimpan di pelayan (autosave, disambung dalam 30 hari) + reset oleh pelanggan.
        Schema::table('builder_sessions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('current_step')->nullable()->after('completed_at');
            $table->timestamp('reset_at')->nullable()->after('current_step');
        });

        // Billing: ganjaran bayar penuh (100%) yang ditetapkan admin.
        Schema::table('billing_settings', function (Blueprint $table): void {
            $table->boolean('full_payment_reward_enabled')->default(false);
            $table->string('full_payment_reward_type', 10)->nullable();           // percent / fixed / addon
            $table->decimal('full_payment_reward_value', 12, 2)->nullable();       // % atau RM
            $table->foreignId('full_payment_reward_addon_id')->nullable()->constrained('addons')->nullOnDelete();
        });

        // Billing: pilihan cara bayar pelanggan bagi quotation (50% deposit atau 100% penuh) + snapshot ganjaran.
        Schema::create('quotation_payment_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('quotation_id')->unique()->constrained()->restrictOnDelete();
            $table->string('plan', 10);                    // DEPOSIT / FULL
            $table->unsignedTinyInteger('percent');        // 50 / 100
            $table->json('reward_snapshot')->nullable();   // {type, value, label, addon_name, addon_value}
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('selected_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_payment_plans');
        Schema::table('billing_settings', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('full_payment_reward_addon_id');
            $table->dropColumn(['full_payment_reward_enabled', 'full_payment_reward_type', 'full_payment_reward_value']);
        });
        Schema::table('builder_sessions', function (Blueprint $table): void {
            $table->dropColumn(['current_step', 'reset_at']);
        });
    }
};
