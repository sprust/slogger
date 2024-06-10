<?php

namespace App\Modules\Auth\Framework\Http\Controllers;

use App\Modules\Auth\Domain\Actions\Interfaces\LoginActionInterface;
use App\Modules\Auth\Domain\Entities\Parameters\LoginParameters;
use App\Modules\Auth\Framework\Http\Requests\LoginRequest;
use App\Modules\Auth\Framework\Http\Resources\LoggedUserResource;
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
                email: $validated['email'],
                password: $validated['password']
            )
        );

        abort_if(!$loggedUser, ResponseFoundation::HTTP_UNAUTHORIZED);

        return new LoggedUserResource($loggedUser);
    }
}
