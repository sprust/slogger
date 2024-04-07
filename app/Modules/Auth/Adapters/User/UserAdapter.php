<?php

namespace App\Modules\Auth\Adapters\User;

use App\Modules\Auth\Domain\Entities\Objects\LoggedUserObject;
use App\Modules\Auth\Domain\Entities\Objects\UserDetailObject;
use App\Modules\User\Domain\Actions\FindUserByEmailAction;
use App\Modules\User\Domain\Actions\FindUserByTokenAction;

readonly class UserAdapter
{
    public function __construct(
        private FindUserByEmailAction $findUserByEmailAction,
        private FindUserByTokenAction $findUserByTokenAction
    ) {
    }

    public function findUserByEmail(string $email): ?UserDetailObject
    {
        $user = $this->findUserByEmailAction->handle(email: $email);

        return $user
            ? new UserDetailObject(
                id: $user->id,
                firstName: $user->firstName,
                lastName: $user->lastName,
                email: $user->email,
                password: $user->password,
                apiToken: $user->apiToken,
                createdAt: $user->createdAt,
                updatedAt: $user->updatedAt,
            )
            : null;
    }

    public function findUserByToken(string $email): ?LoggedUserObject
    {
        $user = $this->findUserByTokenAction->handle($email);

        return $user
            ? new LoggedUserObject(
                id: $user->id,
                firstName: $user->firstName,
                lastName: $user->lastName,
                email: $user->email,
                apiToken: $user->apiToken
            )
            : null;
    }
}
