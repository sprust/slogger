<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Actions;

use App\Modules\Auth\Contracts\Actions\LoginActionInterface;
use App\Modules\Auth\Entities\LoggedUserObject;
use App\Modules\Auth\Parameters\LoginParameters;
use App\Modules\User\Contracts\Domain\FindUserByEmailActionInterface;
use Illuminate\Support\Facades\Hash;

readonly class LoginAction implements LoginActionInterface
{
    public function __construct(
        private FindUserByEmailActionInterface $findUserByEmailAction,
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
