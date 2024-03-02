<?php

namespace App\Modules\Auth\Services;

use App\Models\Users\User;
use App\Modules\Auth\Adapters\AuthUserAdapter;
use App\Modules\Auth\Dto\Parameters\LoginParameters;
use Illuminate\Support\Facades\Hash;

readonly class AuthService
{
    public function __construct(
        private AuthUserAdapter $userAdapter
    ) {
    }

    public function login(LoginParameters $parameters): ?User
    {
        $user = $this->userAdapter->findUserByEmail($parameters->email);

        if (!$user) {
            return null;
        }

        if (!Hash::check($parameters->password, $user->password)) {
            return null;
        }

        return $user;
    }
}
