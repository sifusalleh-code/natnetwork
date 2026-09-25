<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table): void {
            $table->decimal('amount_refunded', 12, 2)->default(0)->after('amount_paid');
        });

        // Billing: refund direkod manual (Billplz tiada API refund). Rekod kekal, tidak dipadam.
        Schema::create('refunds', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->boolean('is_sandbox')->default(false);
            $table->foreignId('invoice_id')->constrained('invoices');
            $table->foreignId('payment_id')->nullable()->constrained('payments');
            $table->foreignId('project_id')->nullable()->constrained('projects');
            $table->foreignId('customer_user_id')->nullable()->constrained('users');
            $table->decimal('amount', 12, 2);
            $table->string('reason', 500);
            $table->string('transfer_reference', 120);
            $table->foreignId('refunded_by_admin_id')->constrained('admins');
            $table->timestamp('refunded_at');
            $table->timestamps();
        });

        // Communication: tetapan Resend (kunci disulitkan) dan log penghantaran.
        Schema::create('communication_settings', function (Blueprint $table): void {
            $table->id();
            $table->boolean('email_enabled')->default(false);
            $table->text('resend_api_key')->nullable();
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->timestamps();
        });
        DB::table('communication_settings')->insert(['email_enabled' => false, 'from_name' => 'NatNetwork Synergy', 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('email_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('recipient');
            $table->string('subject');
            $table->string('category', 30);
            $table->string('channel', 20);
            $table->string('status', 20);
            $table->string('provider_id')->nullable();
            $table->string('error', 500)->nullable();
            $table->timestamp('created_at');
            $table->index(['created_at']);
        });

        // Sales: Change Request selepas quotation diterima. Quotation asal kekal terkunci.
        Schema::create('change_requests', function (Blueprint $table): void {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('project_id')->constrained('projects');
            $table->foreignId('quotation_id')->constrained('quotations');
            $table->foreignId('customer_user_id')->constrained('users');
            $table->string('title', 200);
            $table->text('description');
            $table->string('status', 30);
            $table->string('assessment', 30)->nullable();
            $table->text('admin_note')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->unsignedSmallInteger('extra_weeks')->default(0);
            $table->foreignId('invoice_id')->nullable()->constrained('invoices');
            $table->foreignId('assessed_by_admin_id')->nullable()->constrained('admins');
            $table->timestamp('assessed_at')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->json('decision_metadata')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('change_requests');
        Schema::dropIfExists('email_logs');
        Schema::dropIfExists('communication_settings');
        Schema::dropIfExists('refunds');
        Schema::table('invoices', fn (Blueprint $table) => $table->dropColumn('amount_refunded'));
    }
};
