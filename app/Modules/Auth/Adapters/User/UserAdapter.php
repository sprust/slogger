<?php

namespace App\Modules\Auth\Adapters\User;

use App\Models\Users\User;
use App\Modules\Auth\Adapters\User\Dto\UserFullDto;
use App\Modules\User\Services\UserService;

readonly class UserAdapter
{
    public function __construct(
        private UserService $userService
    ) {
    }

    public function findUserByEmail(string $email): ?UserFullDto
    {
        return $this->makeUserFullDtoByUserOrNull(
            $this->userService->findByEmail($email)
        );
    }

    public function findUserByToken(string $email): ?UserFullDto
    {
        return $this->makeUserFullDtoByUserOrNull(
            $this->userService->findByToken($email)
        );
    }

    private function makeUserFullDtoByUserOrNull(?User $user): ?UserFullDto
    {
        if (!$user) {
            return null;
        }

        return new UserFullDto(
            id: $user->id,
            firstName: $user->first_name,
            lastName: $user->last_name,
            email: $user->email,
            password: $user->password,
            apiToken: $user->api_token,
            createdAt: $user->created_at,
            updatedAt: $user->updated_at,
        );
    }
}
