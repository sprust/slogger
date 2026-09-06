<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Repositories\UserTokenRepository;
use Illuminate\Support\Carbon;

/**
 * Clears out sessions nobody came back to.
 *
 * Housekeeping only: a lapsed session stops authenticating the moment it lapses, whether
 * or not this has run. What it buys is a table that does not grow for ever.
 */
readonly class DeleteExpiredUserTokensAction
{
    public function __construct(
        private UserTokenRepository $userTokenRepository,
    ) {
    }

    public function handle(): int
    {
        return $this->userTokenRepository->deleteExpired(Carbon::now());
    }
}
