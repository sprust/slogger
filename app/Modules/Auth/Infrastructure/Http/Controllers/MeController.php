<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Domain\Actions\FindUserByTokenAction;
use App\Modules\Auth\Infrastructure\Http\Resources\LoggedUserResource;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class MeController
{
    public function __construct(
        private FindUserByTokenAction $findMeByTokenAction
    ) {
    }

    public function __invoke(Request $request): LoggedUserResource
    {
        $bearerToken = $request->bearerToken();

        $me = $bearerToken
            ? $this->findMeByTokenAction->handle($bearerToken)
            : null;

        if ($me === null) {
            abort(ResponseFoundation::HTTP_UNAUTHORIZED);
        }

        return new LoggedUserResource($me);
    }
}
