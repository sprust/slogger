<?php

declare(strict_types=1);

namespace App\Modules\Auth\Contracts\Actions;

use App\Modules\Auth\Entities\LoggedUserObject;
use App\Modules\Auth\Parameters\LoginParameters;

interface LoginActionInterface
{
    public function handle(LoginParameters $parameters): ?LoggedUserObject;
}
