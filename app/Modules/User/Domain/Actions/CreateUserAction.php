<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Parameters\UserCreateParameters;
use App\Modules\User\Repositories\UserRepository;

readonly class CreateUserAction
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    public function handle(UserCreateParameters $parameters): int
    {
        return $this->userRepository->create($parameters);
    }
}
