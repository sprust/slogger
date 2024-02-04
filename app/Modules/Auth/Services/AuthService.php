<?php

namespace App\Modules\Auth\Services;

use App\Models\Users\User;
use App\Modules\Auth\Adapters\AuthUsersAdapter;
use App\Modules\Auth\Dto\Parameters\AuthLoginParameters;
use Illuminate\Support\Facades\Hash;

readonly class AuthService
{
    public function __construct(
        private AuthUsersAdapter $usersAdapter
    ) {
    }

    public function login(AuthLoginParameters $parameters): ?User
    {
        $user = $this->usersAdapter->findUserByEmail($parameters->email);

        if (!$user) {
            return null;
        }

        if (!Hash::check($parameters->password, $user->password)) {
            return null;
        }

        return $user;
    }
}
