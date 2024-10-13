<?php

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Contracts\Domain\CreateUserActionInterface;
use App\Modules\User\Contracts\Repositories\UserRepositoryInterface;
use App\Modules\User\Entities\UserDetailObject;
use App\Modules\User\Parameters\UserCreateParameters;

readonly class CreateUserAction implements CreateUserActionInterface
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function handle(UserCreateParameters $parameters): UserDetailObject
    {
        return $this->userRepository->create($parameters);
    }
}
