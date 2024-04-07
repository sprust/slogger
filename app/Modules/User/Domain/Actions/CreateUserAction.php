<?php

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Domain\Entities\Objects\UserDetailObject;
use App\Modules\User\Domain\Entities\Parameters\UserCreateParameters;
use App\Modules\User\Domain\Transports\UserDetailTransport;
use App\Modules\User\Repository\UserRepositoryInterface;

readonly class CreateUserAction
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function handle(UserCreateParameters $parameters): UserDetailObject
    {
        return UserDetailTransport::toObject(
            $this->userRepository->create(
                firstName: $parameters->firstName,
                lastName: $parameters->lastName,
                email: $parameters->email,
                password: $parameters->password,
            )
        );
    }
}
