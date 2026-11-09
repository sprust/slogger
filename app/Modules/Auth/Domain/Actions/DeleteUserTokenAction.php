<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\Actions;

use App\Modules\User\Domain\Actions\DeleteUserTokenAction as UserDeleteUserTokenAction;
use App\Modules\Auth\Parameters\TokenParameters;

readonly class DeleteUserTokenAction
{
    public function __construct(
        private UserDeleteUserTokenAction $userDeleteUserTokenAction
    ) {
    }

    public function handle(TokenParameters $parameters): void
    {
        $this->userDeleteUserTokenAction->handle($parameters->token);
    }
}
