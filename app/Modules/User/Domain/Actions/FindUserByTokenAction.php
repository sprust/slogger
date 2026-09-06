<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Entities\UserDetailObject;
use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Repositories\UserTokenRepository;
use Illuminate\Support\Carbon;

/**
 * The user a presented token belongs to, or null when it belongs to nobody any more —
 * signed out, or left unused past the idle window.
 */
readonly class FindUserByTokenAction
{
    public function __construct(
        private UserRepository $userRepository,
        private UserTokenRepository $userTokenRepository,
    ) {
    }

    public function handle(string $token): ?UserDetailObject
    {
        $userId = $this->userTokenRepository->findUserIdByToken($token, Carbon::now());

        if ($userId === null) {
            return null;
        }

        return $this->userRepository->findById($userId);
    }
}
