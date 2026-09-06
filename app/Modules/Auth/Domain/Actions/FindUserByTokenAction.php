<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Actions;

use App\Modules\Auth\Entities\LoggedUserObject;
use App\Modules\User\Domain\Actions\FindUserByTokenAction as UserFindUserByTokenAction;

readonly class FindUserByTokenAction
{
    public function __construct(
        private UserFindUserByTokenAction $findUserByTokenAction
    ) {
    }

    public function handle(string $token): ?LoggedUserObject
    {
        $user = $this->findUserByTokenAction->handle($token);

        if (!$user) {
            return null;
        }

        // The token that was presented, not one read back from the user: a session's
        // token exists only in its owner's hands, and /auth/me answers with the same one
        // the caller already has rather than replacing it.
        return new LoggedUserObject(
            id: $user->id,
            firstName: $user->firstName,
            lastName: $user->lastName,
            email: $user->email,
            apiToken: $token
        );
    }
}
