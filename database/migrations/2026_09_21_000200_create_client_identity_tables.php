<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('CUSTOMER')->after('email_verified_at');
            $table->string('company')->nullable()->after('name');
            $table->string('phone')->nullable()->after('company');
            $table->string('password')->nullable()->change();
        });

        Schema::create('email_otp_challenges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('email');
            $table->string('purpose');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('sent_at');
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();
            $table->index(['email', 'purpose', 'sent_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_otp_challenges');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'company', 'phone']);
            $table->string('password')->nullable(false)->change();
        });
    }
};
