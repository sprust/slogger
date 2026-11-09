<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Domain\UseCases\LoginByAdminUseCase;
use App\Modules\Auth\Infrastructure\Http\Requests\LoginRequest;
use App\Modules\Auth\Infrastructure\Http\Resources\LoggedUserResource;
use App\Modules\Auth\Parameters\LoginParameters;
use App\Modules\Common\Helpers\ArrayValueGetter;

readonly class LoginController
{
    public function __construct(private LoginByAdminUseCase $loginByAdminUseCase)
    {
    }

    public function __invoke(LoginRequest $request): LoggedUserResource
    {
        $validated  = $request->validated();
        $loggedUser = $this->loginByAdminUseCase->handle(
            new LoginParameters(
                email: ArrayValueGetter::string(
                    data: $validated,
                    key: 'email',
                ),
                password: ArrayValueGetter::string(
                    data: $validated,
                    key: 'password',
                ),
            )
        );

        return new LoggedUserResource($loggedUser);
    }
}
