<?php

namespace App\Modules\User\Repository;

use App\Models\Users\User;
use App\Modules\User\Repository\Parameters\UserCreateParameters;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserRepository implements UserRepositoryInterface
{
    public function create(UserCreateParameters $parameters): User
    {
        $newUser = new User();

        $newUser->first_name = $parameters->firstName;
        $newUser->last_name  = $parameters->lastName;
        $newUser->email      = $parameters->email;
        $newUser->password   = Hash::make($parameters->password);
        $newUser->api_token  = Str::random(50);

        $newUser->saveOrFail();

        return $newUser;
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function findByToken(string $token): ?User
    {
        return User::query()->where('api_token', $token)->first();
    }
}
