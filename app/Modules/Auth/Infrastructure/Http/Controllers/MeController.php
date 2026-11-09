<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Parameters\TokenParameters;
use App\Modules\Auth\Domain\UseCases\FindUserByTokenByAdminUseCase;
use App\Modules\Auth\Infrastructure\Http\Resources\LoggedUserResource;
use Illuminate\Http\Request;

readonly class MeController
{
    public function __construct(private FindUserByTokenByAdminUseCase $findUserByTokenByAdminUseCase)
    {
    }

    public function __invoke(Request $request): LoggedUserResource
    {
        $bearerToken = $request->bearerToken();
        $me          = $this->findUserByTokenByAdminUseCase->handle(new TokenParameters($bearerToken ?? ''));

        return new LoggedUserResource($me);
    }
}
