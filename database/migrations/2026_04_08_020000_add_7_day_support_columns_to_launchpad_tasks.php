<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('launchpad_tasks', function (Blueprint $table) {
            $table->timestamp('session_completed_at')->nullable()->after('in_phase_2');
            $table->unsignedTinyInteger('fast_track_nudge_count')->default(0)->after('session_completed_at');
            $table->unsignedInteger('total_input_tokens')->default(0)->after('fast_track_nudge_count');
            $table->unsignedInteger('total_output_tokens')->default(0)->after('total_input_tokens');
        });
    }

    public function down(): void
    {
        Schema::table('launchpad_tasks', function (Blueprint $table) {
            $table->dropColumn([
                'session_completed_at',
                'fast_track_nudge_count',
                'total_input_tokens',
                'total_output_tokens',
            ]);
        });
    }
};
