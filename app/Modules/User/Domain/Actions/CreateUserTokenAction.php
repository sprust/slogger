<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Domain\Services\UserTokenLifetimeService;
use App\Modules\User\Repositories\UserTokenRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Opens a session and answers with the token for it.
 *
 * The token is returned and never stored: what the table keeps is its hash, so this is
 * the only moment the value exists anywhere but in the caller's hands.
 */
readonly class CreateUserTokenAction
{
    public function __construct(
        private UserTokenRepository $userTokenRepository,
        private UserTokenLifetimeService $userTokenLifetimeService,
    ) {
    }

    public function handle(int $userId): string
    {
        $token = Str::random(50);

        // One reading of the clock for both stamps: taken twice, the row would say it was
        // created a moment before it was last used.
        $now = Carbon::now();

        $this->userTokenRepository->create(
            userId: $userId,
            token: $token,
            now: $now,
            expiresAt: $this->userTokenLifetimeService->expiresAt($now),
        );

        return $token;
    }
}
