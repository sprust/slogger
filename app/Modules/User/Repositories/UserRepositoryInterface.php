<?php

namespace App\Modules\User\Repositories;

use App\Modules\User\Repositories\Dto\UserDetailDto;

interface UserRepositoryInterface
{
    public function create(
        string $firstName,
        ?string $lastName,
        string $email,
        string $password
    ): UserDetailDto;

    public function findByEmail(string $email): ?UserDetailDto;

    public function findByToken(string $token): ?UserDetailDto;
}
