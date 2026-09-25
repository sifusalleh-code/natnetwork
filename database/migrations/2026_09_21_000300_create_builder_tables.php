<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builder_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('resume_token')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('entry_path')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_company')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('builder_questions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('label');
            $table->text('helper')->nullable();
            $table->string('question_type');
            $table->json('service_categories')->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->json('condition')->nullable();
            $table->json('internal_mapping')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('builder_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('builder_question_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('label');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['builder_question_id', 'code']);
        });

        Schema::create('builder_answers', function (Blueprint $table) {
            $table->id();
            $table->uuid('builder_session_id');
            $table->foreign('builder_session_id')->references('id')->on('builder_sessions')->cascadeOnDelete();
            $table->foreignId('builder_question_id')->constrained()->cascadeOnDelete();
            $table->json('value')->nullable();
            $table->text('text_value')->nullable();
            $table->timestamps();
            $table->unique(['builder_session_id', 'builder_question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_answers');
        Schema::dropIfExists('builder_question_options');
        Schema::dropIfExists('builder_questions');
        Schema::dropIfExists('builder_sessions');
    }
};
