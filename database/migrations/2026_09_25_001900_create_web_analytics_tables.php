<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Analytics Engine: paparan halaman awam (first-party, tanpa IP; negara daripada header Cloudflare).
        Schema::create('web_page_views', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->uuid('visitor_token');
            $table->uuid('session_token');
            $table->string('route_name', 60)->nullable();
            $table->string('path', 500);
            $table->boolean('is_entry')->default(false);        // halaman pertama sesi (landing page)
            $table->boolean('is_new_visitor')->default(false);  // paparan pertama pelawat (tiada cookie pelawat)
            $table->string('source', 20)->nullable();           // hanya pada is_entry: organic/social/direct/referral/affiliate/campaign
            $table->string('source_host', 120)->nullable();
            $table->string('referrer', 500)->nullable();
            $table->string('utm_source', 120)->nullable();
            $table->string('utm_medium', 120)->nullable();
            $table->string('utm_campaign', 120)->nullable();
            $table->string('device_type', 10);                  // mobile/tablet/desktop
            $table->string('browser', 30);
            $table->char('country', 2)->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable(); // dikira di pelayan daripada ping
            $table->timestamp('viewed_at');
            $table->timestamp('last_seen_at');
            $table->index('viewed_at');
            $table->index('last_seen_at');
            $table->index(['session_token', 'viewed_at']);
            $table->index(['visitor_token', 'viewed_at']);
        });

        Schema::create('web_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('web_page_view_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('visitor_token');
            $table->uuid('session_token');
            $table->string('name', 30);   // cta_click
            $table->string('label', 40);  // kunci CTA
            $table->string('path', 500);
            $table->timestamp('occurred_at');
            $table->index(['name', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('web_events');
        Schema::dropIfExists('web_page_views');
    }
};
