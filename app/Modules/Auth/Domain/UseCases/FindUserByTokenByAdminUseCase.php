<?php

declare(strict_types=1);

namespace App\Modules\Auth\Domain\UseCases;

use App\Modules\Auth\Domain\Exceptions\AuthenticationFailedException;
use App\Modules\Auth\Parameters\TokenParameters;
use App\Modules\Auth\Entities\LoggedUserObject;
use App\Modules\Auth\Domain\Actions\FindUserByTokenAction;

readonly class FindUserByTokenByAdminUseCase
{
    public function __construct(
        private FindUserByTokenAction $findUserByTokenAction
    ) {
    }

    /**
     * @throws AuthenticationFailedException
     */
    public function handle(TokenParameters $parameters): LoggedUserObject
    {
        $token = $parameters->token;

        if (!$token) {
            throw new AuthenticationFailedException();
        }

        return $this->findUserByTokenAction->handle(
            token: $token,
        ) ?? throw new AuthenticationFailedException();
    }
}
