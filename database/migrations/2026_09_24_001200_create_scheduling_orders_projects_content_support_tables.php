<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->unsignedSmallInteger('estimated_weeks')->nullable()->after('total_amount');
        });

        // ---- Scheduling ----
        Schema::create('scheduling_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('max_active_projects')->default(3);
            $table->unsignedSmallInteger('hold_minutes')->default(60);
            $table->unsignedSmallInteger('weeks_ahead')->default(12);
            $table->timestamps();
        });
        DB::table('scheduling_settings')->insert(['max_active_projects' => 3, 'hold_minutes' => 60, 'weeks_ahead' => 12, 'created_at' => now(), 'updated_at' => now()]);

        Schema::create('slot_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->restrictOnDelete();
            $table->date('start_date');
            $table->unsignedSmallInteger('weeks');
            $table->string('status', 20); // HELD, RESERVED, RELEASED, EXPIRED
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('reserved_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->boolean('conflict')->default(false);
            $table->timestamps();
            $table->index(['status', 'start_date']);
        });

        // ---- Orders ----
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('quotation_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('customer_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('slot_hold_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status', 20); // CONFIRMED, CANCELLED
            $table->timestamp('confirmed_at');
            $table->timestamps();
        });

        // ---- Projects ----
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('order_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('quotation_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_user_id')->constrained('users')->restrictOnDelete();
            $table->string('name');
            $table->string('template', 40);
            $table->string('status', 30);
            $table->unsignedTinyInteger('progress')->default(0);
            $table->date('planned_start_date')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->unsignedBigInteger('started_by_admin_id')->nullable();
            $table->foreignId('final_invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->string('status_note', 500)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedSmallInteger('support_days')->default(0);
            $table->timestamps();
            $table->index(['customer_user_id', 'status']);
        });

        Schema::create('project_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->string('name');
            $table->string('client_label');
            $table->unsignedTinyInteger('weight');
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('completed_by_admin_id')->nullable();
            $table->timestamps();
        });

        Schema::create('project_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('reason', 500)->nullable();
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->timestamp('created_at');
        });

        // ---- Project Content ----
        Schema::create('project_content_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('kind', 10); // FILE, INFO
            $table->string('status', 20); // REQUESTED, SUBMITTED, ACCEPTED, NEEDS_UPDATE
            $table->boolean('blocking')->default(false);
            $table->text('info_text')->nullable();
            $table->string('admin_note', 500)->nullable();
            $table->boolean('help_requested')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('content_item_id')->nullable()->constrained('project_content_items')->nullOnDelete();
            $table->string('uploaded_by', 10); // CLIENT, ADMIN
            $table->string('visibility', 10); // CLIENT, INTERNAL
            $table->boolean('is_deliverable')->default(false);
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('path');
            $table->string('original_name');
            $table->string('mime', 120);
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->index(['project_id', 'visibility']);
        });

        // ---- Support ----
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('customer_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->string('status', 20); // OPEN, ANSWERED, CLOSED
            $table->timestamp('last_activity_at');
            $table->timestamps();
        });

        Schema::create('support_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->cascadeOnDelete();
            $table->string('author_type', 10); // CLIENT, ADMIN
            $table->unsignedBigInteger('author_id');
            $table->text('body');
            $table->timestamp('created_at');
        });

        // ---- Communication ----
        Schema::create('portal_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('recipient_type', 20); // CLIENT, AFFILIATE, ADMIN
            $table->unsignedBigInteger('recipient_id');
            $table->string('category', 30);
            $table->string('title');
            $table->string('body', 500)->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['recipient_type', 'recipient_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portal_notifications');
        Schema::dropIfExists('support_messages');
        Schema::dropIfExists('support_tickets');
        Schema::dropIfExists('project_files');
        Schema::dropIfExists('project_content_items');
        Schema::dropIfExists('project_status_logs');
        Schema::dropIfExists('project_milestones');
        Schema::dropIfExists('projects');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('slot_holds');
        Schema::dropIfExists('scheduling_settings');
        Schema::table('quotations', fn (Blueprint $table) => $table->dropColumn('estimated_weeks'));
    }
};
