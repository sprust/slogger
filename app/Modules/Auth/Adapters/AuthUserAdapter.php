<?php

namespace App\Modules\Auth\Adapters;

use App\Models\Users\User;
use App\Modules\User\Services\UserService;

readonly class AuthUserAdapter
{
    public function __construct(
        private UserService $userService
    ) {
    }

    public function findUserByEmail(string $email): ?User
    {
        return $this->userService->findByEmail($email);
    }

    public function findUserByToken(string $email): ?User
    {
        return $this->userService->findByToken($email);
    }
}
