<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('launchpad_tasks', function (Blueprint $table) {
            $table->string('assistant_name')->nullable()->after('status');
            $table->string('bottleneck_summary')->nullable()->after('assistant_name');
        });
    }

    public function down(): void
    {
        Schema::table('launchpad_tasks', function (Blueprint $table) {
            $table->dropColumn(['assistant_name', 'bottleneck_summary']);
        });
    }
};
