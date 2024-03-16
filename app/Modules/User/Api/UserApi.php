<?php

namespace App\Modules\User\Api;

use App\Modules\User\Services\Objects\UserFullObject;
use App\Modules\User\Services\UserService;

readonly class UserApi
{
    public function __construct(
        private UserService $userService
    ) {
    }

    public function findByEmail(string $email): ?UserFullObject
    {
        return $this->userService->findByEmail($email);
    }

    public function findByToken(string $token): ?UserFullObject
    {
        return $this->userService->findByToken($token);
    }

}
