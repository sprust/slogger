<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Moves the api token off the user and into a row per signed-in session.
 *
 * `users.api_token` was one string per account: never rotated, shared by every device,
 * stored in clear, and handed back in every /auth/me. Signing out cleared it from the
 * browser and nothing else, so nothing could ever be revoked short of editing the column.
 *
 * Existing tokens are carried over rather than reissued — nobody is signed out by this.
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

        $now = now();

        foreach (DB::table('users')->select('id', 'api_token')->cursor() as $user) {
            DB::table('user_tokens')->insert([
                'user_id'      => $user->id,
                'token_hash'   => hash('sha256', $user->api_token),
                'created_at'   => $now,
                'last_used_at' => $now,
                // The idle window starts now for everyone: there is no record of when any
                // of these was last used, and assuming the worst would sign people out.
                'expires_at'   => $now->copy()->addDays(15),
            ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('api_token');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable where the original was not, because the column is filled in the
            // loop below rather than by the schema change itself. Note what that loop
            // means: the tokens in user_tokens are hashes, so nothing can be carried back
            // the way up() carried them forward. A rollback issues everyone a new string
            // that nobody holds — which signs every session out, the exact opposite of
            // what up() promises.
            $table->string('api_token', 80)->nullable()->unique();
        });

        foreach (DB::table('users')->select('id')->cursor() as $user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update(['api_token' => Str::random(50)]);
        }

        Schema::dropIfExists('user_tokens');
    }
};
