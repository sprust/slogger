<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Contracts\Actions\LoginActionInterface;
use App\Modules\Auth\Infrastructure\Http\Requests\LoginRequest;
use App\Modules\Auth\Infrastructure\Http\Resources\LoggedUserResource;
use App\Modules\Auth\Parameters\LoginParameters;
use App\Modules\Common\Helpers\ArrayValueGetter;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class LoginController
{
    public function __construct(
        private LoginActionInterface $loginAction
    ) {
    }

    public function __invoke(LoginRequest $request): LoggedUserResource
    {
        $validated = $request->validated();

        $loggedUser = $this->loginAction->handle(
            new LoginParameters(
                email: ArrayValueGetter::string($validated, 'email'),
                password: ArrayValueGetter::string($validated, 'password')
            )
        );

        abort_if(!$loggedUser, ResponseFoundation::HTTP_UNAUTHORIZED);

        return new LoggedUserResource($loggedUser);
    }
}
