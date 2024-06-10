<?php

namespace App\Modules\User\Repositories;

use App\Models\Users\User;
use App\Modules\User\Repositories\Dto\UserDetailDto;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRepository implements UserRepositoryInterface
{
    public function create(
        string $firstName,
        ?string $lastName,
        string $email,
        string $password
    ): UserDetailDto {
        $newUser = new User();

        $newUser->first_name = $firstName;
        $newUser->last_name  = $lastName;
        $newUser->email      = $email;
        $newUser->password   = Hash::make($password);
        $newUser->api_token  = Str::random(50);

        $newUser->saveOrFail();

        return $this->makeUserFullDtoByUserOrNull($newUser);
    }

    public function findByEmail(string $email): ?UserDetailDto
    {
        return $this->makeUserFullDtoByUserOrNull(
            User::query()->where('email', $email)->first()
        );
    }

    public function findByToken(string $token): ?UserDetailDto
    {
        return $this->makeUserFullDtoByUserOrNull(
            User::query()->where('api_token', $token)->first()
        );
    }


    private function makeUserFullDtoByUserOrNull(?User $user): ?UserDetailDto
    {
        if (!$user) {
            return null;
        }

        return new UserDetailDto(
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
