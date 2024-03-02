<?php

namespace App\Modules\User\Services;

use App\Models\Users\User;
use App\Modules\User\Repository\Parameters\UserCreateParameters;
use App\Modules\User\Repository\UserRepositoryInterface;

readonly class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function create(UserCreateParameters $parameters): User
    {
        return $this->userRepository->create($parameters);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    public function findByToken(string $token): ?User
    {
        return $this->userRepository->findByToken($token);
    }
}
