<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Services;

use Illuminate\Support\Carbon;

/**
 * How long a session outlives its last use.
 *
 * The window is idle time, not age: every authenticated request pushes the expiry out
 * again, so a session ends when its owner stops using it rather than on a fixed date.
 * One place holds the number because two of them move it — the login that opens a
 * session and the middleware that renews one.
 *
 * Takes the moment rather than reading the clock, so that the two stamps written together
 * are the same moment and a caller can ask what an expiry would be at any other one.
 */
readonly class UserTokenLifetimeService
{
    public const int IDLE_DAYS = 15;

    public function expiresAt(Carbon $now): Carbon
    {
        return $now->copy()->addDays(self::IDLE_DAYS);
    }
}
