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
        Schema::create('trace_clearing_settings', function (Blueprint $table) {
            $table->id();

            $table->string('type')->nullable();
            $table->unsignedInteger('days_lifetime');
            $table->boolean('only_data');

            $table->timestamps();

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trace_clearing_settings');
    }
};
