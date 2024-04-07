<?php

namespace App\Modules\Auth\Domain\Actions;

use App\Modules\Auth\Adapters\User\UserAdapter;
use App\Modules\Auth\Domain\Entities\Objects\LoggedUserObject;
use App\Modules\Auth\Domain\Entities\Parameters\LoginParameters;
use Illuminate\Support\Facades\Hash;

readonly class LoginAction
{
    public function __construct(
        private UserAdapter $userAdapter
    ) {
    }

    public function handle(LoginParameters $parameters): ?LoggedUserObject
    {
        $user = $this->userAdapter->findUserByEmail($parameters->email);

        if (!$user) {
            return null;
        }

        if (!Hash::check($parameters->password, $user->password)) {
            return null;
        }

        return new LoggedUserObject(
        id: $user->id,
        firstName: $user->firstName,
        lastName: $user->lastName,
        email: $user->email,
        apiToken: $user->apiToken
    );
    }
}
