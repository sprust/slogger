<?php

namespace App\Modules\Auth\Domain\Actions;

use App\Modules\Auth\Domain\Actions\Interfaces\LoginActionInterface;
use App\Modules\Auth\Domain\Entities\Objects\LoggedUserObject;
use App\Modules\Auth\Domain\Entities\Parameters\LoginParameters;
use App\Modules\User\Domain\Actions\FindUserByEmailAction;
use Illuminate\Support\Facades\Hash;

readonly class LoginAction implements LoginActionInterface
{
    public function __construct(
        private FindUserByEmailAction $findUserByEmailAction,
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
            apiToken: $user->apiToken
        );
    }
}
