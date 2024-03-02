<?php

namespace App\Modules\Auth\Adapters;

use App\Models\Users\User;
use App\Modules\User\Repository\UserRepositoryInterface;

readonly class AuthUserAdapter
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function findUserByEmail(string $email): ?User
    {
        return $this->userRepository->findByEmail($email);
    }

    public function findUserByToken(string $email): ?User
    {
        return $this->userRepository->findByToken($email);
    }
}
