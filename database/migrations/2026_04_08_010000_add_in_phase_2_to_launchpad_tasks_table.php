<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('launchpad_tasks', function (Blueprint $table) {
            $table->boolean('in_phase_2')->default(false)->after('phase_1_complete');
        });
    }

    public function down(): void
    {
        Schema::table('launchpad_tasks', function (Blueprint $table) {
            $table->dropColumn('in_phase_2');
        });
    }
};
