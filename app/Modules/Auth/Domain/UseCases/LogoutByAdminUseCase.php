<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\UseCases;

use App\Modules\Auth\Domain\Actions\DeleteUserTokenAction;
use App\Modules\Auth\Parameters\TokenParameters;

readonly class LogoutByAdminUseCase
{
    public function __construct(
        private DeleteUserTokenAction $deleteUserTokenAction
    ) {
    }

    public function handle(TokenParameters $parameters): void
    {
        $this->deleteUserTokenAction->handle($parameters);
    }
}
