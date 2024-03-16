<?php

namespace App\Modules\User\Repository;

use App\Models\Users\User;
use App\Modules\User\Repository\Dto\UserFullDto;
use App\Modules\User\Repository\Parameters\UserCreateParameters;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRepository implements UserRepositoryInterface
{
    public function create(UserCreateParameters $parameters): UserFullDto
    {
        $newUser = new User();

        $newUser->first_name = $parameters->firstName;
        $newUser->last_name  = $parameters->lastName;
        $newUser->email      = $parameters->email;
        $newUser->password   = Hash::make($parameters->password);
        $newUser->api_token  = Str::random(50);

        $newUser->saveOrFail();

        return $this->makeUserFullDtoByUserOrNull($newUser);
    }

    public function findByEmail(string $email): ?UserFullDto
    {
        return $this->makeUserFullDtoByUserOrNull(
            User::query()->where('email', $email)->first()
        );
    }

    public function findByToken(string $token): ?UserFullDto
    {
        return $this->makeUserFullDtoByUserOrNull(
            User::query()->where('api_token', $token)->first()
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
