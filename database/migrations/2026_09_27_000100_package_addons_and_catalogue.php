<?php

use Database\Seeders\PricingCatalogSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pricing: senarai "Included dalam Pakej" bagi setiap pakej.
        Schema::table('service_packages', function (Blueprint $table): void {
            $table->json('inclusions')->nullable()->after('summary');
        });

        // Pricing: kedudukan add-on dalam Shared Add-on Catalogue (null = tidak dipaparkan dalam katalog).
        Schema::table('addons', function (Blueprint $table): void {
            $table->unsignedSmallInteger('catalogue_order')->nullable()->after('display_order');
        });

        // Pricing: add-on yang ditawarkan oleh setiap pakej, dengan harga ikut pakej.
        Schema::create('package_addons', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('service_package_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->foreignId('addon_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('name')->nullable();
            $table->string('price_type');
            $table->decimal('price_amount', 12, 2)->nullable();
            $table->string('price_label');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['service_package_id', 'addon_id']);
        });

        // Katalog sedia ada (production) dikemas kini terus; pangkalan data kosong diisi melalui db:seed seperti biasa.
        if (DB::table('services')->exists()) {
            (new PricingCatalogSeeder())->run();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('package_addons');
        Schema::table('addons', function (Blueprint $table): void {
            $table->dropColumn('catalogue_order');
        });
        Schema::table('service_packages', function (Blueprint $table): void {
            $table->dropColumn('inclusions');
        });
    }
};
