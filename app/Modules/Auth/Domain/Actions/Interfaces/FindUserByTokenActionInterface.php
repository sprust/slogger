<?php

namespace App\Modules\Auth\Domain\Actions\Interfaces;

use App\Modules\Auth\Domain\Entities\Objects\LoggedUserObject;

interface FindUserByTokenActionInterface
{
    public function handle(string $token): ?LoggedUserObject;
}
