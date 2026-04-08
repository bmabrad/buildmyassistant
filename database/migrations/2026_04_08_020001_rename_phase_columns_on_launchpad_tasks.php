<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('launchpad_tasks', function (Blueprint $table) {
            $table->renameColumn('phase_1_complete', 'playbook_delivered');
            $table->renameColumn('in_phase_2', 'in_post_playbook');
        });
    }

    public function down(): void
    {
        Schema::table('launchpad_tasks', function (Blueprint $table) {
            $table->renameColumn('playbook_delivered', 'phase_1_complete');
            $table->renameColumn('in_post_playbook', 'in_phase_2');
        });
    }
};
