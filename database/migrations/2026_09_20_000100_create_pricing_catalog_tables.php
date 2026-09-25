<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id(); $table->string('slug')->unique(); $table->string('name'); $table->text('summary')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('service_packages', function (Blueprint $table) {
            $table->id(); $table->foreignId('service_id')->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('slug')->unique(); $table->string('name'); $table->text('summary')->nullable(); $table->string('price_type');
            $table->decimal('price_amount', 12, 2)->nullable(); $table->string('price_label'); $table->string('delivery_estimate')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('addons', function (Blueprint $table) {
            $table->id(); $table->string('slug')->unique(); $table->string('name'); $table->text('summary')->nullable(); $table->string('price_type');
            $table->decimal('price_amount', 12, 2)->nullable(); $table->string('price_label'); $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true); $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addons'); Schema::dropIfExists('service_packages'); Schema::dropIfExists('services');
    }
};
