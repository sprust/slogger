<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Repositories\UserTokenRepository;

/**
 * Ends one session. The other sessions of the same account are left alone — signing out
 * of one browser is not signing out of the others.
 */
readonly class DeleteUserTokenAction
{
    public function __construct(
        private UserTokenRepository $userTokenRepository,
    ) {
    }

    public function handle(string $token): bool
    {
        return $this->userTokenRepository->delete($token);
    }
}
