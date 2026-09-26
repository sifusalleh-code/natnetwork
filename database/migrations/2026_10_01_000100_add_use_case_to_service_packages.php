<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kesesuaian penggunaan setiap pakej (contoh: "SME dan perniagaan yang mempunyai beberapa
 * produk/servis..."), dipaparkan bersama spec/inclusions di halaman Services dan Start Project
 * supaya pelanggan faham spec + kegunaan + tahap kos dalam satu tempat.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_packages', function (Blueprint $table): void {
            $table->text('use_case')->nullable()->after('inclusions');
        });
    }

    public function down(): void
    {
        Schema::table('service_packages', fn (Blueprint $table) => $table->dropColumn('use_case'));
    }
};
