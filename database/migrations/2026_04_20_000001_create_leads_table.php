<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source')->default('launchpad');
            $table->string('status')->default('partial');

            // Chat inputs
            $table->string('buyer_name')->nullable();
            $table->string('buyer_email')->nullable();
            $table->text('business_description')->nullable();
            $table->string('business_role')->nullable();
            $table->text('who_they_serve')->nullable();
            $table->string('tools_used')->nullable();
            $table->text('time_drains')->nullable();
            $table->text('tedious_work')->nullable();
            $table->text('one_handoff_today')->nullable();
            $table->string('ai_usage_level')->nullable();

            // Derived from generation
            $table->string('business_type')->nullable();
            $table->string('assistant_pick')->nullable();

            $table->timestamp('completed_at')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('buyer_email');
            $table->index('status');
            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
