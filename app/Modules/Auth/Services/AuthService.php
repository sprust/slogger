<?php

namespace App\Modules\Auth\Services;

use App\Modules\Auth\Adapters\User\Dto\UserFullDto;
use App\Modules\Auth\Adapters\User\UserAdapter;
use App\Modules\Auth\Dto\Objects\LoggedUserObject;
use App\Modules\Auth\Dto\Parameters\LoginParameters;
use Illuminate\Support\Facades\Hash;

readonly class AuthService
{
    public function __construct(
        private UserAdapter $userAdapter
    ) {
    }

    public function me(string $token): ?LoggedUserObject
    {
        $user = $this->userAdapter->findUserByToken($token);

        if (!$user) {
            return null;
        }

        return $this->makeLoggedUserObjectByFullDto($user);
    }

    public function login(LoginParameters $parameters): ?LoggedUserObject
    {
        $user = $this->userAdapter->findUserByEmail($parameters->email);

        if (!$user) {
            return null;
        }

        if (!Hash::check($parameters->password, $user->password)) {
            return null;
        }

        return $this->makeLoggedUserObjectByFullDto($user);
    }

    private function makeLoggedUserObjectByFullDto(UserFullDto $userFull): LoggedUserObject
    {
        return new LoggedUserObject(
            id: $userFull->id,
            firstName: $userFull->firstName,
            lastName: $userFull->lastName,
            email: $userFull->email,
            apiToken: $userFull->apiToken
        );
    }
}
