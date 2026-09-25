<?php

use Database\Seeders\BuilderQuestionSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('builder_files', function (Blueprint $table): void {
            $table->id();
            $table->uuid('builder_session_id');
            $table->foreign('builder_session_id')->references('id')->on('builder_sessions')->cascadeOnDelete();
            $table->string('kind', 20);            // logo / reference
            $table->string('original_name', 190);
            $table->string('path');
            $table->string('mime', 100);
            $table->unsignedInteger('size');
            $table->timestamps();
            $table->index(['builder_session_id', 'kind']);
        });

        // Kemas kini soalan builder (pilihan Warna, Logo, Image, Domain, Rujukan, Budget). Idempotent.
        if (Schema::hasTable('builder_questions') && \Illuminate\Support\Facades\DB::table('builder_questions')->exists()) {
            (new BuilderQuestionSeeder())->run();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_files');
    }
};
