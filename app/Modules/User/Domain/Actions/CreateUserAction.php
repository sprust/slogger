<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Domain\Exceptions\UserAlreadyExistsException;
use App\Modules\User\Parameters\UserCreateParameters;
use App\Modules\User\Repositories\UserRepository;

readonly class CreateUserAction
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    /**
     * @throws UserAlreadyExistsException
     */
    public function handle(UserCreateParameters $parameters): int
    {
        if ($this->userRepository->isExistByEmail($parameters->email)) {
            throw new UserAlreadyExistsException($parameters->email);
        }

        return $this->userRepository->create($parameters);
    }
}
