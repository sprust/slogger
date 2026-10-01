<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which channel a watcher speaks through. Null means it speaks nowhere — the incident is
 * still opened in the panel, it is just not carried out of it.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('watchers', function (Blueprint $table) {
            // nullOnDelete rather than a cascade: losing the channel must not take the
            // watcher with it.
            $table->foreignId('notification_channel_id')
                ->nullable()
                ->after('cooldown_seconds')
                ->constrained('notification_channels')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('watchers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('notification_channel_id');
        });
    }
};
