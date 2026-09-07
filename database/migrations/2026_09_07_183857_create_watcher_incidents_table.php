<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The fact a watcher found something, kept open until a person closes it.
 *
 * No uniqueness on the open one: a watcher may cover several filters' worth of trouble,
 * and one process writes these anyway — the task pool. The index is for the lookup.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('watcher_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watcher_id')->constrained()->cascadeOnDelete();
            // `opened` / `closed` — WatcherIncidentStatusEnum.
            $table->string('status', 16);

            $table->timestamp('first_event_at');
            $table->timestamp('last_event_at');

            // Denormalised so a listing does not count rows of the events table per line.
            $table->unsignedInteger('events_count')->default(0);

            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['watcher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watcher_incidents');
    }
};
