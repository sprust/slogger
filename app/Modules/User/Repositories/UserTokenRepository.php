<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Models\Users\UserToken;
use Illuminate\Support\Carbon;

/**
 * The sessions table, addressed by the token the caller presented.
 *
 * Hashing lives here rather than above it: what the column holds is a storage decision,
 * and every method that touches the table has to agree on it. Callers pass the token they
 * were given and never see the hash.
 *
 * The current time does not: it arrives as an argument. When a session began, when it was
 * last used and whether it has lapsed are the domain's to decide, and a table that reads
 * the clock for itself cannot be told to answer as of any other moment.
 */
class UserTokenRepository
{
    public function create(int $userId, string $token, Carbon $now, Carbon $expiresAt): void
    {
        $userToken = new UserToken();

        $userToken->user_id      = $userId;
        $userToken->token_hash   = $this->hash($token);
        $userToken->created_at   = $now;
        $userToken->last_used_at = $now;
        $userToken->expires_at   = $expiresAt;

        $userToken->saveOrFail();
    }

    /**
     * The owner of a token that is still good as of $now.
     *
     * The expiry is checked here rather than left to the sweep: a session is over the
     * moment it lapses, not whenever the next cleanup happens to run.
     */
    public function findUserIdByToken(string $token, Carbon $now): ?int
    {
        $userToken = UserToken::query()
            ->where('token_hash', $this->hash($token))
            ->where('expires_at', '>', $now)
            ->first();

        return $userToken?->user_id;
    }

    /**
     * Pushes a session's expiry out, provided it has not already lapsed as of $now.
     *
     * The predicate is the whole point. Without it a token presented after its window had
     * closed — refused, but presented — would carry its own row forward another fifteen
     * days, and a session could be kept alive for ever by the requests it is rejecting.
     */
    public function touch(string $token, Carbon $now, Carbon $expiresAt): bool
    {
        return UserToken::query()
            ->where('token_hash', $this->hash($token))
            ->where('expires_at', '>', $now)
            ->update([
                'last_used_at' => $now,
                'expires_at'   => $expiresAt,
            ]) > 0;
    }

    public function delete(string $token): bool
    {
        return UserToken::query()
            ->where('token_hash', $this->hash($token))
            ->delete() > 0;
    }

    public function deleteExpired(Carbon $now): int
    {
        return UserToken::query()
            ->where('expires_at', '<=', $now)
            ->delete();
    }

    private function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
