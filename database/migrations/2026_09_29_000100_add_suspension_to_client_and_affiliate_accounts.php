<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bulk action akaun ahli (Admin > Akaun pengguna): Suspend/Activate memerlukan status pada
 * akaun Client dan Affiliate. Partner sudah mempunyai `status` (PENDING_REVIEW/APPROVED/
 * REJECTED/SUSPENDED) — lajur sama ditambah di sini untuk konsisten dan supaya akaun tergantung
 * disekat log masuk di semua tiga jenis akaun ahli dengan cara yang sama.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('suspended_at')->nullable()->after('email_verified_at');
            $table->string('suspended_reason', 500)->nullable()->after('suspended_at');
            $table->unsignedBigInteger('suspended_by_admin_id')->nullable()->after('suspended_reason');
        });
        Schema::table('affiliates', function (Blueprint $table): void {
            $table->timestamp('suspended_at')->nullable()->after('email_verified_at');
            $table->string('suspended_reason', 500)->nullable()->after('suspended_at');
            $table->unsignedBigInteger('suspended_by_admin_id')->nullable()->after('suspended_reason');
        });
    }

    public function down(): void
    {
        Schema::table('affiliates', fn (Blueprint $table) => $table->dropColumn(['suspended_at', 'suspended_reason', 'suspended_by_admin_id']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['suspended_at', 'suspended_reason', 'suspended_by_admin_id']));
    }
};
