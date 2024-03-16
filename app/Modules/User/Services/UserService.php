<?php

namespace App\Modules\User\Services;

use App\Modules\User\Repository\Dto\UserFullDto;
use App\Modules\User\Repository\Parameters\UserCreateParameters;
use App\Modules\User\Repository\UserRepositoryInterface;
use App\Modules\User\Services\Objects\UserFullObject;

readonly class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository
    ) {
    }

    public function create(UserCreateParameters $parameters): UserFullObject
    {
        return $this->makeObjectByDtoOrNull(
            $this->userRepository->create($parameters)
        );
    }

    public function findByEmail(string $email): ?UserFullObject
    {
        return $this->makeObjectByDtoOrNull(
            $this->userRepository->findByEmail($email)
        );
    }

    public function findByToken(string $token): ?UserFullObject
    {
        return $this->makeObjectByDtoOrNull(
            $this->userRepository->findByToken($token)
        );
    }

    private function makeObjectByDtoOrNull(UserFullDto $dto): ?UserFullObject
    {
        return new UserFullObject(
            id: $dto->id,
            firstName: $dto->firstName,
            lastName: $dto->lastName,
            email: $dto->email,
            password: $dto->password,
            apiToken: $dto->apiToken,
            createdAt: $dto->createdAt,
            updatedAt: $dto->updatedAt
        );
    }
}
