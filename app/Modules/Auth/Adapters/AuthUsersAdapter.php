<?php

namespace App\Modules\Auth\Adapters;

use App\Models\Users\User;
use App\Modules\Users\Repository\UsersRepository;

readonly class AuthUsersAdapter
{
    public function __construct(
        private UsersRepository $usersRepository
    ) {
    }

    public function findUserByEmail(string $email): ?User
    {
        return $this->usersRepository->findByEmail($email);
    }
}
