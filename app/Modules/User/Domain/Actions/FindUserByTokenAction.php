<?php

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Contracts\Domain\FindUserByTokenActionInterface;
use App\Modules\User\Contracts\Repositories\UserRepositoryInterface;
use App\Modules\User\Entities\UserDetailObject;

readonly class FindUserByTokenAction implements FindUserByTokenActionInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function handle(string $token): ?UserDetailObject
    {
        return $this->userRepository->findByToken(token: $token);
    }
}
