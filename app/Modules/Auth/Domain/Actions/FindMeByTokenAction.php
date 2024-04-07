<?php

namespace App\Modules\Auth\Domain\Actions;

use App\Modules\Auth\Adapters\User\UserAdapter;
use App\Modules\Auth\Domain\Entities\Objects\LoggedUserObject;

readonly class FindMeByTokenAction
{
    public function __construct(
        private UserAdapter $userAdapter
    ) {
    }

    public function handle(string $token): ?LoggedUserObject
    {
        return $this->userAdapter->findUserByToken($token);
    }
}
