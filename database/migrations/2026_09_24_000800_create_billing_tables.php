<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_settings', function (Blueprint $table) {
            $table->id();
            $table->string('active_mode', 20)->default('SANDBOX');
            $table->text('sandbox_api_key')->nullable();
            $table->string('sandbox_collection_id', 60)->nullable();
            $table->text('sandbox_x_signature_key')->nullable();
            $table->text('production_api_key')->nullable();
            $table->string('production_collection_id', 60)->nullable();
            $table->text('production_x_signature_key')->nullable();
            $table->timestamps();
        });
        DB::table('billing_settings')->insert(['active_mode' => 'SANDBOX', 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('prefix', 20);
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('last_number')->default(0);
            $table->timestamps();
            $table->unique(['prefix', 'year']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->boolean('is_sandbox')->default(false)->index();
            $table->string('type', 30);
            $table->string('status', 30);
            $table->foreignId('customer_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('source_type', 80)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('seller_snapshot');
            $table->json('customer_snapshot');
            $table->json('items_snapshot');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->timestamp('issued_at');
            $table->date('due_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->index(['source_type', 'source_id']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->boolean('is_sandbox')->default(false)->index();
            $table->string('gateway', 20)->default('BILLPLZ');
            $table->string('gateway_mode', 20);
            $table->string('gateway_bill_id', 60)->nullable()->unique();
            $table->string('gateway_url', 500)->nullable();
            $table->string('gateway_collection_id', 60)->nullable();
            $table->unsignedBigInteger('amount_cents');
            $table->unsignedBigInteger('paid_amount_cents')->nullable();
            $table->string('status', 30);
            $table->string('review_reason', 500)->nullable();
            $table->json('callback_payload')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['invoice_id', 'status']);
        });

        Schema::create('receipts', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->boolean('is_sandbox')->default(false)->index();
            $table->foreignId('payment_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamp('issued_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('billing_settings');
    }
};
