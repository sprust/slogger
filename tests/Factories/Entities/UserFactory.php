<?php

declare(strict_types=1);

namespace Tests\Factories\Entities;

use App\Modules\User\Repositories\UserRepository;
use App\Modules\User\Parameters\UserCreateParameters;

readonly class UserFactory
{
    public function __construct(private UserRepository $userRepository)
    {
    }

    public function create(
        ?string $firstName = null,
        ?string $lastName = null,
        ?string $email = null,
        ?string $password = null,
    ): int {
        return $this->userRepository->create(
            new UserCreateParameters(
                firstName: $firstName ?? fake()->firstName(),
                lastName: $lastName ?? fake()->lastName(),
                email: $email ?? fake()->uuid() . '@example.test',
                password: $password ?? fake()->password(),
            )
        );
    }
}
