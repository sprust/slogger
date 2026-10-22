<?php

declare(strict_types=1);

namespace App\Modules\User\Domain\Actions;

use App\Modules\User\Domain\Exceptions\UserNotFoundException;
use App\Modules\User\Parameters\UserUpdateParameters;
use App\Modules\User\Repositories\UserRepository;

readonly class UpdateUserAction
{
    public function __construct(
        private UserRepository $userRepository
    ) {
    }

    /**
     * @throws UserNotFoundException
     */
    public function handle(UserUpdateParameters $parameters): void
    {
        if (is_null($this->userRepository->findOneById($parameters->id))) {
            throw new UserNotFoundException($parameters->id);
        }

        $this->userRepository->updateName(
            id: $parameters->id,
            firstName: $parameters->firstName,
            lastName: $parameters->lastName
        );
    }
}
