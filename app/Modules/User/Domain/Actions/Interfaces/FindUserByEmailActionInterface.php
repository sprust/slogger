<?php

namespace App\Modules\User\Domain\Actions\Interfaces;

use App\Modules\User\Domain\Entities\Objects\UserDetailObject;

interface FindUserByEmailActionInterface
{
    public function handle(string $email): ?UserDetailObject;
}
