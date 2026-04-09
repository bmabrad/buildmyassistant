<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('launchpad_messages', function (Blueprint $table) {
            $table->renameColumn('is_instruction_sheet', 'is_deliverable');
        });

        Schema::table('launchpad_messages', function (Blueprint $table) {
            $table->longText('playbook_content')->nullable()->after('is_deliverable');
            $table->longText('instructions_content')->nullable()->after('playbook_content');
        });
    }

    public function down(): void
    {
        Schema::table('launchpad_messages', function (Blueprint $table) {
            $table->dropColumn(['playbook_content', 'instructions_content']);
        });

        Schema::table('launchpad_messages', function (Blueprint $table) {
            $table->renameColumn('is_deliverable', 'is_instruction_sheet');
        });
    }
};
