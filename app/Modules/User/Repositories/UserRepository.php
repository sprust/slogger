<?php

declare(strict_types=1);

namespace App\Modules\User\Repositories;

use App\Models\Users\User;
use App\Modules\User\Contracts\Repositories\UserRepositoryInterface;
use App\Modules\User\Entities\UserDetailObject;
use App\Modules\User\Parameters\UserCreateParameters;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRepository implements UserRepositoryInterface
{
    public function create(UserCreateParameters $parameters): UserDetailObject
    {
        $newUser = new User();

        $newUser->first_name = $parameters->firstName;
        $newUser->last_name  = $parameters->lastName;
        $newUser->email      = $parameters->email;
        $newUser->password   = Hash::make($parameters->password);
        $newUser->api_token  = Str::random(50);

        $newUser->saveOrFail();

        return $this->makeUserFullObjectByUserOrNull($newUser);
    }

    public function findByEmail(string $email): ?UserDetailObject
    {
        return $this->makeUserFullObjectByUserOrNull(
            User::query()->where('email', $email)->first()
        );
    }

    public function findByToken(string $token): ?UserDetailObject
    {
        return $this->makeUserFullObjectByUserOrNull(
            User::query()->where('api_token', $token)->first()
        );
    }


    private function makeUserFullObjectByUserOrNull(?User $user): ?UserDetailObject
    {
        if (!$user) {
            return null;
        }

        return new UserDetailObject(
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
