<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sales: add-on yang dipilih pelanggan dalam Start Project (harga daripada Pricing Engine).
        Schema::table('builder_sessions', function (Blueprint $table): void {
            $table->json('addon_ids')->nullable()->after('service_package_id');
        });

        // Soalan julat bajet digantikan dengan kos sebenar (pakej + add-on). Rekod dikekalkan, hanya dinyahaktifkan.
        DB::table('builder_questions')->where('code', 'budget')->update(['is_active' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        DB::table('builder_questions')->where('code', 'budget')->update(['is_active' => true, 'updated_at' => now()]);
        Schema::table('builder_sessions', function (Blueprint $table): void {
            $table->dropColumn('addon_ids');
        });
    }
};
