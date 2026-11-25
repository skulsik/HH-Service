<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hh_sync_statuses', function (Blueprint $table) {
            $table->timestamp('scheduler_last_seen_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hh_sync_statuses', function (Blueprint $table) {
            $table->dropColumn('scheduler_last_seen_at');
        });
    }
};
