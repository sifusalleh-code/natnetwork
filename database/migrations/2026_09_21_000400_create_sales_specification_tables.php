<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_user_id')->constrained('users')->restrictOnDelete();
            $table->uuid('builder_session_id')->unique();
            $table->foreign('builder_session_id')->references('id')->on('builder_sessions')->restrictOnDelete();
            $table->foreignId('service_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('DRAFT');
            $table->json('requirement_snapshot');
            $table->timestamps();
        });

        Schema::create('master_specifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_request_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('status')->default('DRAFT');
            $table->json('requirement_snapshot');
            $table->json('specification_snapshot');
            $table->json('technical_mapping')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();
            $table->unique(['project_request_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_specifications');
        Schema::dropIfExists('project_requests');
    }
};
