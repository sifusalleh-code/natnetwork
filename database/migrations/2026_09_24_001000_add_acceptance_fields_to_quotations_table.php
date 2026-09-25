<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->foreignId('accepted_by_user_id')->nullable()->after('accepted_at')->constrained('users')->nullOnDelete();
            $table->json('acceptance_metadata')->nullable()->after('accepted_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('accepted_by_user_id');
            $table->dropColumn('acceptance_metadata');
        });
    }
};
