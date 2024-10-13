<?php

namespace App\Modules\User\Contracts\Repositories;

use App\Modules\User\Entities\UserDetailObject;
use App\Modules\User\Parameters\UserCreateParameters;

interface UserRepositoryInterface
{
    public function create(UserCreateParameters $parameters): UserDetailObject;

    public function findByEmail(string $email): ?UserDetailObject;

    public function findByToken(string $token): ?UserDetailObject;
}
