<?php

namespace App\Modules\Auth\Domain\Actions;

use App\Modules\Auth\Domain\Actions\Interfaces\FindUserByTokenActionInterface;
use App\Modules\Auth\Domain\Entities\Objects\LoggedUserObject;
use App\Modules\User\Domain\Actions\FindUserByTokenAction as UserFindUserByTokenAction;

readonly class FindUserByTokenAction implements FindUserByTokenActionInterface
{
    public function __construct(
        private UserFindUserByTokenAction $findUserByTokenAction
    ) {
    }

    public function handle(string $token): ?LoggedUserObject
    {
        $user = $this->findUserByTokenAction->handle($token);

        if (!$user) {
            return null;
        }

        return new LoggedUserObject(
            id: $user->id,
            firstName: $user->firstName,
            lastName: $user->lastName,
            email: $user->email,
            apiToken: $user->apiToken
        );
    }
}
