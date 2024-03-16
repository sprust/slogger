<?php

namespace App\Modules\User\Repository;

use App\Modules\User\Repository\Dto\UserFullDto;
use App\Modules\User\Repository\Parameters\UserCreateParameters;

interface UserRepositoryInterface
{
    public function create(UserCreateParameters $parameters): UserFullDto;

    public function findByEmail(string $email): ?UserFullDto;

    public function findByToken(string $token): ?UserFullDto;
}
