<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('partners', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone', 50);
            $table->string('id_type', 20);                 // IC / COMPANY
            $table->text('id_number');                     // encrypted
            $table->string('id_last4', 4)->nullable();
            $table->string('company_name')->nullable();
            $table->string('bank_name', 120)->nullable();
            $table->string('bank_account_holder', 120)->nullable();
            $table->text('bank_account_number')->nullable(); // encrypted
            $table->string('bank_account_last4', 4)->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('status', 30)->default('PENDING_REVIEW');
            $table->string('review_note', 500)->nullable();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admins');
            $table->timestamp('reviewed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('partner_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('program_enabled')->default(false);
            $table->decimal('pool_percent', 5, 2)->default(10);
            $table->decimal('min_capital', 12, 2)->default(5000);
            $table->decimal('max_total_capital', 14, 2)->default(100000);
            $table->timestamps();
        });
        DB::table('partner_settings')->insert(['program_enabled' => false, 'pool_percent' => 10, 'min_capital' => 5000, 'max_total_capital' => 100000, 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('partner_capitals', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->boolean('is_sandbox')->default(false);
            $table->foreignId('partner_id')->constrained('partners');
            $table->decimal('amount', 12, 2);
            $table->string('status', 30);                  // PENDING_PAYMENT / ACTIVE / CANCELLED
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->json('terms_snapshot');
            $table->json('acceptance_metadata');
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        // Pool 10% setiap jualan pelanggan yang dibayar. Satu ALLOCATION setiap invois; REVERSAL setiap refund.
        Schema::create('partner_pool_entries', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 20);
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->foreignId('refund_id')->nullable()->constrained('refunds');
            $table->decimal('sale_amount', 12, 2);
            $table->decimal('pool_percent', 5, 2);
            $table->decimal('pool_amount', 12, 2);
            $table->decimal('allocated_amount', 12, 2);
            $table->decimal('total_capital', 14, 2);
            $table->timestamp('created_at');
            $table->index(['invoice_id', 'type']);
        });

        Schema::create('partner_earnings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('partner_pool_entry_id')->constrained('partner_pool_entries');
            $table->foreignId('partner_id')->constrained('partners');
            $table->decimal('capital', 14, 2);
            $table->decimal('amount', 12, 2);
            $table->timestamp('created_at');
            $table->index(['partner_id', 'created_at']);
        });

        Schema::create('partner_payouts', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('partner_id')->constrained('partners');
            $table->decimal('amount', 12, 2);
            $table->string('transfer_reference', 120);
            $table->string('note', 500)->nullable();
            $table->foreignId('paid_by_admin_id')->constrained('admins');
            $table->timestamp('paid_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['partner_payouts', 'partner_earnings', 'partner_pool_entries', 'partner_capitals', 'partner_settings', 'partners'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
