<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_request_id')->constrained()->restrictOnDelete();
            $table->foreignId('master_specification_id')->constrained()->restrictOnDelete();
            $table->string('number')->nullable()->unique();
            $table->string('status')->default('DRAFT');
            $table->json('price_snapshot');
            $table->json('terms_snapshot')->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('invalidated_at')->nullable();
            $table->timestamps();
            $table->index(['master_specification_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
