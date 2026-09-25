<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('username', 12)->nullable()->unique();
            $table->string('phone', 50)->nullable();
            $table->string('state', 40)->nullable();
            $table->string('avatar_path')->nullable();
            $table->string('bank_name', 120)->nullable();
            $table->text('bank_account_number')->nullable();
            $table->string('bank_account_last4', 4)->nullable();
            $table->string('facebook_url', 300)->nullable();
            $table->string('instagram_url', 300)->nullable();
            $table->string('twitter_url', 300)->nullable();
            $table->string('status')->default('ACTIVE');
            $table->timestamp('profile_completed_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('affiliate_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->restrictOnDelete();
            $table->uuid('visitor_token');
            $table->string('source', 20);
            $table->string('device_type', 10);
            $table->string('referrer', 500)->nullable();
            $table->string('landing_path', 500);
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamp('clicked_at');
            $table->index(['affiliate_id', 'clicked_at']);
            $table->index('visitor_token');
        });

        Schema::create('affiliate_client_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_user_id')->unique()->constrained('users')->restrictOnDelete();
            $table->foreignId('affiliate_click_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('linked_at');
            $table->timestamps();
        });

        Schema::create('affiliate_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('cookie_days')->default(30);
            $table->timestamps();
        });

        DB::table('affiliate_settings')->insert(['cookie_days' => 30, 'created_at' => now(), 'updated_at' => now()]);
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_settings');
        Schema::dropIfExists('affiliate_client_links');
        Schema::dropIfExists('affiliate_clicks');
        Schema::dropIfExists('affiliates');
    }
};
