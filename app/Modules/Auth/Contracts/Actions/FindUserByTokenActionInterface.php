<?php

namespace App\Modules\Auth\Contracts\Actions;

use App\Modules\Auth\Entities\LoggedUserObject;

interface FindUserByTokenActionInterface
{
    public function handle(string $token): ?LoggedUserObject;
}
