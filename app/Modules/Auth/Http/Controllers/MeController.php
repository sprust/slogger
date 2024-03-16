<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Modules\Auth\Dto\Objects\LoggedUserObject;
use App\Modules\Auth\Http\Resources\LoggedUserResource;
use App\Modules\Auth\Services\AuthService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class MeController
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    public function __invoke(Request $request): LoggedUserResource
    {
        $bearerToken = $request->bearerToken();

        abort_if(!$bearerToken, ResponseFoundation::HTTP_UNAUTHORIZED);

        $me = $this->authService->me($bearerToken);

        abort_if(!$me, ResponseFoundation::HTTP_UNAUTHORIZED);

        return new LoggedUserResource(
            new LoggedUserObject(
                id: $me->id,
                firstName: $me->firstName,
                lastName: $me->lastName,
                email: $me->email,
                apiToken: $me->apiToken
            )
        );
    }
}
