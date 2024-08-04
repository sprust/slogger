<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trace_clearing_settings', function (Blueprint $table) {
            $table->dropUnique(['type']);

            $table->boolean('only_data')->after('days_lifetime')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trace_clearing_settings', function (Blueprint $table) {
            $table->dropColumn('only_data');

            $table->unique(['type']);
        });
    }
};
