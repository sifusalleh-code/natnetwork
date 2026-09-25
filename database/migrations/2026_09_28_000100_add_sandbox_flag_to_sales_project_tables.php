<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pemisahan rekod sandbox/production (Keputusan Owner 25 Sep 2026, #2).
 * Quotation, slot hold, order dan projek kini membawa penanda is_sandbox yang sama sepanjang rantaian,
 * supaya data ujian (mod Billplz SANDBOX) tidak pernah bercampur dengan kapasiti atau rekod production sebenar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $table->boolean('is_sandbox')->default(false)->after('id');
        });
        Schema::table('slot_holds', function (Blueprint $table): void {
            $table->boolean('is_sandbox')->default(false)->after('quotation_id');
        });
        Schema::table('orders', function (Blueprint $table): void {
            $table->boolean('is_sandbox')->default(false)->after('quotation_id');
        });
        Schema::table('projects', function (Blueprint $table): void {
            $table->boolean('is_sandbox')->default(false)->after('order_id');
        });
        Schema::table('change_requests', function (Blueprint $table): void {
            $table->boolean('is_sandbox')->default(false)->after('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('change_requests', fn (Blueprint $table) => $table->dropColumn('is_sandbox'));
        Schema::table('projects', fn (Blueprint $table) => $table->dropColumn('is_sandbox'));
        Schema::table('orders', fn (Blueprint $table) => $table->dropColumn('is_sandbox'));
        Schema::table('slot_holds', fn (Blueprint $table) => $table->dropColumn('is_sandbox'));
        Schema::table('quotations', fn (Blueprint $table) => $table->dropColumn('is_sandbox'));
    }
};
