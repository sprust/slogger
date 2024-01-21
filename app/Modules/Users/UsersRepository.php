<?php

namespace App\Modules\Users;

use App\Models\Users\User;
use App\Modules\Users\Parameters\UsersCreateParameters;
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
}
