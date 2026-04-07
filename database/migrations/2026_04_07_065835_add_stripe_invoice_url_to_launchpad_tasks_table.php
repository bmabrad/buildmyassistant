<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('launchpad_tasks', function (Blueprint $table) {
            $table->string('stripe_invoice_url', 512)->nullable()->after('stripe_customer_id');
        });
    }

    public function down(): void
    {
        Schema::table('launchpad_tasks', function (Blueprint $table) {
            $table->dropColumn('stripe_invoice_url');
        });
    }
};
