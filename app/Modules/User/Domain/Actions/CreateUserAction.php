<?php

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Domain\Actions\Interfaces\CreateUserActionInterface;
use App\Modules\User\Domain\Entities\Objects\UserDetailObject;
use App\Modules\User\Domain\Entities\Parameters\UserCreateParameters;
use App\Modules\User\Domain\Entities\Transports\UserDetailTransport;
use App\Modules\User\Repositories\UserRepositoryInterface;

readonly class CreateUserAction implements CreateUserActionInterface
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
