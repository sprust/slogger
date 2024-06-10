<?php

namespace App\Modules\User\Domain\Actions\Interfaces;

use App\Modules\User\Domain\Entities\Objects\UserDetailObject;
use App\Modules\User\Domain\Entities\Parameters\UserCreateParameters;

interface CreateUserActionInterface
{
    public function handle(UserCreateParameters $parameters): UserDetailObject;
}
