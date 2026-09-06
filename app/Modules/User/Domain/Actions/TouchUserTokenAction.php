<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Domain\Services\UserTokenLifetimeService;
use App\Modules\User\Repositories\UserTokenRepository;
use Illuminate\Support\Carbon;

/**
 * Pushes a session's expiry out, which is what makes the window idle time rather than age.
 *
 * Answers whether there was a session to push — false for one that has already been
 * deleted or has lapsed since the request began.
 */
readonly class TouchUserTokenAction
{
    public function __construct(
        private UserTokenRepository $userTokenRepository,
        private UserTokenLifetimeService $userTokenLifetimeService,
    ) {
    }

    public function handle(string $token): bool
    {
        $now = Carbon::now();

        return $this->userTokenRepository->touch(
            token: $token,
            now: $now,
            expiresAt: $this->userTokenLifetimeService->expiresAt($now),
        );
    }
}
