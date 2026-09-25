<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('site_name', 80);
            $table->string('default_description', 300)->nullable();
            $table->string('default_og_image')->nullable();
            $table->string('google_site_verification', 120)->nullable();
            $table->string('bing_site_verification', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('seo_pages', function (Blueprint $table): void {
            $table->id();
            $table->string('route_name', 120)->unique();
            $table->string('title', 90)->nullable();
            $table->string('description', 300)->nullable();
            $table->string('og_image')->nullable();
            $table->boolean('noindex')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_pages');
        Schema::dropIfExists('seo_settings');
    }
};
