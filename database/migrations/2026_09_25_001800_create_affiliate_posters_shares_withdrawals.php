<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliate_posters', function (Blueprint $table): void {
            $table->id();
            $table->string('title', 120);
            $table->text('caption');
            $table->string('image_path');
            $table->string('image_mime', 50);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->foreignId('created_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('affiliate_shares', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('affiliate_poster_id')->nullable()->constrained()->nullOnDelete();
            $table->string('channel', 20);
            $table->timestamp('shared_at');
            $table->index(['affiliate_id', 'shared_at']);
        });

        Schema::create('affiliate_withdrawals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('status', 20)->default('REQUESTED'); // REQUESTED / PAID / REJECTED
            $table->string('bank_name', 120);
            $table->text('bank_account_number');                // encrypted snapshot
            $table->string('bank_account_last4', 4)->nullable();
            $table->string('account_holder', 190);
            $table->unsignedInteger('week_shares');
            $table->unsignedInteger('week_unique_clicks');
            $table->string('reference', 120)->nullable();
            $table->string('note', 500)->nullable();
            $table->foreignId('processed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->index(['affiliate_id', 'status']);
        });

        Schema::table('affiliate_settings', function (Blueprint $table): void {
            $table->unsignedSmallInteger('weekly_share_target')->default(20);
            $table->unsignedSmallInteger('weekly_unique_click_target')->default(100);
        });
    }

    public function down(): void
    {
        Schema::table('affiliate_settings', fn (Blueprint $table) => $table->dropColumn(['weekly_share_target', 'weekly_unique_click_target']));
        Schema::dropIfExists('affiliate_withdrawals');
        Schema::dropIfExists('affiliate_shares');
        Schema::dropIfExists('affiliate_posters');
    }
};
