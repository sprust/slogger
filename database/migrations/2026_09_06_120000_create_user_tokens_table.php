<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A row per signed-in session: a token that can expire and be revoked one by one, stored
 * as a hash.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // The hash, not the token. Unique because a lookup is by it, and a collision
            // would mean two sessions answering to one presented token.
            $table->string('token_hash', 64)->unique();

            $table->timestamp('created_at');
            $table->timestamp('last_used_at');

            // Indexed for the sweep that deletes what has gone stale, not for the lookup —
            // that one arrives by hash and checks this column on the row it found.
            $table->timestamp('expires_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_tokens');
    }
};
