<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('launchpad_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->enum('role', ['user', 'assistant']);
            $table->longText('content');
            $table->tinyInteger('phase')->unsigned()->nullable();
            $table->boolean('is_instruction_sheet')->default(false);
            $table->timestamp('created_at')->nullable();

            $table->index('task_id');
            $table->index(['task_id', 'created_at']);
            $table->index(['task_id', 'is_instruction_sheet']);

            $table->foreign('task_id')
                ->references('id')
                ->on('launchpad_tasks')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('launchpad_messages');
    }
};
