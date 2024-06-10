<?php

namespace App\Modules\Auth\Framework\Http\Controllers;

use App\Modules\Auth\Domain\Actions\Interfaces\FindUserByTokenActionInterface;
use App\Modules\Auth\Framework\Http\Resources\LoggedUserResource;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class MeController
{
    public function __construct(
        private FindUserByTokenActionInterface $findMeByTokenAction
    ) {
    }

    public function __invoke(Request $request): LoggedUserResource
    {
        $bearerToken = $request->bearerToken();

        $me = $bearerToken
            ? $this->findMeByTokenAction->handle($bearerToken)
            : null;

        abort_if(!$me, ResponseFoundation::HTTP_UNAUTHORIZED);

        return new LoggedUserResource($me);
    }
}
