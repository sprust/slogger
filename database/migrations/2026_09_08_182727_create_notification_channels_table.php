<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_channels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 64);
            $table->boolean('enabled')->default(true)->index();

            $table->text('settings');

            $table->timestamps();
            $table->softDeletes();
        });

        // Here rather than with the watchers, which are created first. nullOnDelete rather
        // than a cascade: losing the channel must not take the watcher with it.
        Schema::table('watchers', function (Blueprint $table) {
            $table->foreign('notification_channel_id')
                ->references('id')
                ->on('notification_channels')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('watchers', function (Blueprint $table) {
            $table->dropForeign(['notification_channel_id']);
        });

        Schema::dropIfExists('notification_channels');
    }
};
