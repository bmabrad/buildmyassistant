<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->string('product');
            $table->string('name');
            $table->longText('system_prompt');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['product', 'name']);
            $table->index(['product', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prompt_versions');
    }
};
