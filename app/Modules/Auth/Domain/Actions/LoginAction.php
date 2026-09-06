<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Actions;

use App\Modules\Auth\Entities\LoggedUserObject;
use App\Modules\Auth\Parameters\LoginParameters;
use App\Modules\User\Domain\Actions\CreateUserTokenAction;
use App\Modules\User\Domain\Actions\FindUserByEmailAction;
use Illuminate\Support\Facades\Hash;

readonly class LoginAction
{
    public function __construct(
        private FindUserByEmailAction $findUserByEmailAction,
        private CreateUserTokenAction $createUserTokenAction,
    ) {
    }

    public function handle(LoginParameters $parameters): ?LoggedUserObject
    {
        $user = $this->findUserByEmailAction->handle($parameters->email);

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
            // A session of its own, so that signing in on a second device does not hand
            // out the first one's token and signing out of one does not end both.
            apiToken: $this->createUserTokenAction->handle($user->id)
        );
    }
}
