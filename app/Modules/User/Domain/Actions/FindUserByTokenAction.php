<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Entities\UserDetailObject;
use App\Modules\User\Repositories\UserRepository;

readonly class FindUserByTokenAction
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function handle(string $token): ?UserDetailObject
    {
        return $this->userRepository->findByToken(token: $token);
    }
}
