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

            $table->boolean('on_opened')->default(true);
            $table->boolean('on_event')->default(false);
            $table->boolean('on_closed')->default(true);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_channels');
    }
};
