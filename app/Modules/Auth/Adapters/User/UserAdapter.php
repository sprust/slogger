<?php

namespace App\Modules\Auth\Adapters\User;

use App\Modules\Auth\Adapters\User\Dto\UserFullDto;
use App\Modules\User\Api\UserApi;
use App\Modules\User\Services\Objects\UserFullObject;

readonly class UserAdapter
{
    public function __construct(
        private UserApi $userApi
    ) {
    }

    public function findUserByEmail(string $email): ?UserFullDto
    {
        return $this->makeObjectToDtoOrNull(
            $this->userApi->findByEmail($email)
        );
    }

    public function findUserByToken(string $email): ?UserFullDto
    {
        return $this->makeObjectToDtoOrNull(
            $this->userApi->findByToken($email)
        );
    }

    private function makeObjectToDtoOrNull(?UserFullObject $user): ?UserFullDto
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
