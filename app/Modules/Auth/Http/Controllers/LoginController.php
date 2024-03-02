<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Dto\Parameters\LoginParameters;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Resources\UserResource;
use App\Modules\Auth\Services\AuthService;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class LoginController
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    public function __invoke(LoginRequest $request): UserResource
    {
        $validated = $request->validated();

        $user = $this->authService->login(
            new LoginParameters(
                email: $validated['email'],
                password: $validated['password']
            )
        );

        abort_if(!$user, ResponseFoundation::HTTP_UNAUTHORIZED);

        return new UserResource($user);
    }
}
