<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Controllers;

use App\Modules\Auth\Domain\UseCases\LogoutByAdminUseCase;
use App\Modules\Auth\Parameters\TokenParameters;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Ends the session the request arrived on.
 *
 * Only that one: the same account signed in elsewhere stays signed in. The panel used to
 * do this by clearing its own storage, which left the token working for anyone who had a
 * copy of it.
 */
readonly class LogoutController
{
    public function __construct(private LogoutByAdminUseCase $logoutByAdminUseCase)
    {
    }

    public function __invoke(Request $request): Response
    {
        $bearerToken = $request->bearerToken();

        if ($bearerToken) {
            $this->logoutByAdminUseCase->handle(
                new TokenParameters(
                    token: $bearerToken,
                )
            );
        }

        return response()->noContent();
    }
}
