<?php

namespace App\Modules\Auth\Domain\Actions\Interfaces;

use App\Modules\Auth\Domain\Entities\Objects\LoggedUserObject;
use App\Modules\Auth\Domain\Entities\Parameters\LoginParameters;

interface LoginActionInterface
{
    public function handle(LoginParameters $parameters): ?LoggedUserObject;
}
