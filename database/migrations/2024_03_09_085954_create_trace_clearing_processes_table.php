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
        Schema::create('trace_clearing_processes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('setting_id')->constrained('trace_clearing_settings')->cascadeOnDelete();
            $table->unsignedInteger('cleared_count')->default(0);
            $table->timestamp('cleared_at')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trace_clearing_processes');
    }
};
