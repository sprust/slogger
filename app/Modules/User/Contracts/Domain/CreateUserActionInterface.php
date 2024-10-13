<?php

namespace App\Modules\User\Contracts\Domain;

use App\Modules\User\Entities\UserDetailObject;
use App\Modules\User\Parameters\UserCreateParameters;

interface CreateUserActionInterface
{
    public function handle(UserCreateParameters $parameters): UserDetailObject;
}
