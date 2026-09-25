<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->timestamp('payment_completed_at')->nullable()->after('acceptance_metadata');
            $table->unsignedBigInteger('payment_completed_by_admin_id')->nullable()->after('payment_completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['payment_completed_at', 'payment_completed_by_admin_id']);
        });
    }
};
