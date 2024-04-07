<?php

namespace App\Modules\Auth\Adapters\User;

use App\Modules\Auth\Adapters\User\Dto\UserFullDto;
use App\Modules\User\Domain\Actions\FindUserByEmailAction;
use App\Modules\User\Domain\Actions\FindUserByTokenAction;
use App\Modules\User\Domain\Entities\Objects\UserDetailObject;

readonly class UserAdapter
{
    public function __construct(
        private FindUserByEmailAction $findUserByEmailAction,
        private FindUserByTokenAction $findUserByTokenAction
    ) {
    }

    public function findUserByEmail(string $email): ?UserFullDto
    {
        return $this->makeObjectToDtoOrNull(
            $this->findUserByEmailAction->handle($email)
        );
    }

    public function findUserByToken(string $email): ?UserFullDto
    {
        return $this->makeObjectToDtoOrNull(
            $this->findUserByTokenAction->handle($email)
        );
    }

    private function makeObjectToDtoOrNull(?UserDetailObject $user): ?UserFullDto
    {
        if (!$user) {
            return null;
        }

        return new UserFullDto(
            id: $user->id,
            firstName: $user->firstName,
            lastName: $user->lastName,
            email: $user->email,
            password: $user->password,
            apiToken: $user->apiToken,
            createdAt: $user->createdAt,
            updatedAt: $user->updatedAt,
        );
    }
}
