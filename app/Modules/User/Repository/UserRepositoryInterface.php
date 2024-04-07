<?php

namespace App\Modules\User\Repository;

use App\Modules\User\Repository\Dto\UserDetailDto;

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
