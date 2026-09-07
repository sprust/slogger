<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The watchers themselves: what is watched, how often it may speak, and the one part of
 * that the receiver reads.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('watchers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 64);

            // Indexed because both readers filter on it and nothing else: the task pool
            // checks the enabled ones, and the Go receiver loads the enabled ones that
            // carry a filter.
            $table->boolean('enabled')->default(true)->index();

            // The contract with the receiver, and the reason it is a column of its own
            // rather than a key inside `settings`: what another service reads should be
            // visible in the schema. NULL means the watcher is about the buffers, which
            // the receiver knows nothing about.
            //
            // Not named `match`: that is a reserved word in MySQL (MATCH ... AGAINST), and
            // the receiver's raw SQL would have to quote it for ever after.
            $table->json('trace_match')->nullable();

            // Thresholds, windows, percentages. Read only by the panel.
            $table->json('settings');

            $table->unsignedInteger('cooldown_seconds');

            // Since when the watcher's timeline is worth believing. A check that needs a
            // whole window waits until one has been collected, instead of reporting
            // silence that is only the absence of collection.
            $table->timestamp('collect_since')->nullable();

            $table->timestamp('last_checked_at')->nullable();
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watchers');
    }
};
