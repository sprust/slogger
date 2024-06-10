<?php

namespace App\Modules\User\Domain\Actions\Interfaces;

use App\Modules\User\Domain\Entities\Objects\UserDetailObject;

interface FindUserByTokenActionInterface
{
    public function handle(string $token): ?UserDetailObject;
}
