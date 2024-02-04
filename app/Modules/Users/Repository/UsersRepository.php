<?php

namespace App\Modules\Users\Repository;

use App\Models\Users\User;
use App\Modules\Users\Repository\Parameters\UsersCreateParameters;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersRepository
{
    public function create(UsersCreateParameters $parameters): User
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

    public function exists(string $email): bool
    {
        return User::query()->where('email', $email)->exists();
    }

    public function findByEmail(string $email): User
    {
        return User::query()->where('email', $email)->first();
    }

    public function findByToken(string $token): User
    {
        return User::query()->where('api_token', $token)->first();
    }
}
