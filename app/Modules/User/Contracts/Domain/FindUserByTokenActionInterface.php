<?php

declare(strict_types=1);

namespace App\Modules\User\Contracts\Domain;

use App\Modules\User\Entities\UserDetailObject;

interface FindUserByTokenActionInterface
{
    public function handle(string $token): ?UserDetailObject;
}
