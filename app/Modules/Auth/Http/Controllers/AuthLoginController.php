<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Dto\Parameters\AuthLoginParameters;
use App\Modules\Auth\Http\Requests\AuthLoginRequest;
use App\Modules\Auth\Http\Resources\AuthUserResource;
use App\Modules\Auth\Services\AuthService;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class AuthLoginController
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    public function __invoke(AuthLoginRequest $request): AuthUserResource
    {
        $validated = $request->validated();

        $user = $this->authService->login(
            new AuthLoginParameters(
                email: $validated['email'],
                password: $validated['password']
            )
        );

        abort_if(!$user, ResponseFoundation::HTTP_UNAUTHORIZED);

        return new AuthUserResource($user);
    }
}
