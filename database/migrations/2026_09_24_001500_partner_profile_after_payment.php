<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Aliran baharu Partnership: maklumat asas + bayaran dahulu, KYC (IC & bank) dilengkapkan selepas bayaran berjaya.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->text('id_number')->nullable()->change();
            $table->timestamp('terms_accepted_at')->nullable()->after('email_verified_at');
            $table->timestamp('profile_completed_at')->nullable()->after('terms_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('partners', function (Blueprint $table): void {
            $table->dropColumn(['terms_accepted_at', 'profile_completed_at']);
        });
    }
};
