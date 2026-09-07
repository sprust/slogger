<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every time a watcher spoke, under the incident it spoke about.
 *
 * The volume is bounded by the watcher's cooldown rather than by the traffic: at five
 * minutes that is 288 rows a day per watcher.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('watcher_incident_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained('watcher_incidents')->cascadeOnDelete();
            $table->timestamp('occurred_at')->index();

            // What the watcher saw: the value, the threshold it crossed, and the rollup
            // of the traces behind it. Shapeless on purpose — every type reports its own
            // numbers, and the panel only renders them.
            $table->json('payload');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('watcher_incident_events');
    }
};
