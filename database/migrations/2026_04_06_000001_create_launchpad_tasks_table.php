<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('launchpad_tasks', function (Blueprint $table) {
            $table->id();
            $table->string('token', 36)->unique();
            $table->string('stripe_payment_id', 255);
            $table->string('stripe_customer_id', 255)->nullable()->index();
            $table->string('name', 255);
            $table->string('email', 255)->index();
            $table->enum('status', ['pending', 'active', 'completed'])->default('pending')->index();
            $table->tinyInteger('phase')->unsigned()->default(1);
            $table->boolean('phase_1_complete')->default(false);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index('stripe_payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('launchpad_tasks');
    }
};
