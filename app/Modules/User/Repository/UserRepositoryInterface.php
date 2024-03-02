<?php

namespace App\Modules\User\Repository;

use App\Models\Users\User;
use App\Modules\User\Repository\Parameters\UserCreateParameters;

interface UserRepositoryInterface
{
    public function create(UserCreateParameters $parameters): User;

    public function findByEmail(string $email): ?User;

    public function findByToken(string $token): ?User;
}
