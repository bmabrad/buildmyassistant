<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('generations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prompt_version_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('retry_of_id')->nullable()->constrained('generations')->nullOnDelete();
            $table->string('product')->default('launchpad');
            $table->string('model');
            $table->string('status')->default('pending');
            $table->json('input_payload')->nullable();
            $table->json('output')->nullable();
            $table->longText('raw_response')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->unsignedInteger('input_tokens')->nullable();
            $table->unsignedInteger('output_tokens')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'status']);
            $table->index('product');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generations');
    }
};
