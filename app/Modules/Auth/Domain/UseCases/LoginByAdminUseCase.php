<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\UseCases;

use App\Modules\Auth\Domain\Exceptions\AuthenticationFailedException;
use App\Modules\Auth\Entities\LoggedUserObject;
use App\Modules\Auth\Parameters\LoginParameters;
use App\Modules\Auth\Domain\Actions\LoginAction;

readonly class LoginByAdminUseCase
{
    public function __construct(private LoginAction $loginAction)
    {
    }

    /**
     * @throws AuthenticationFailedException
     */
    public function handle(LoginParameters $parameters): LoggedUserObject
    {
        return $this->loginAction->handle(
            parameters: $parameters,
        ) ?? throw new AuthenticationFailedException();
    }
}
