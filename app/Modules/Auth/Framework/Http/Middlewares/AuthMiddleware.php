<?php

namespace App\Modules\Auth\Framework\Http\Middlewares;

use App\Modules\Auth\Domain\Actions\FindUserByTokenAction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class AuthMiddleware
{
    public function __construct(
        private FindUserByTokenAction $findUserByTokenAction
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            abort(401);
        }

        $user = $this->findUserByTokenAction->handle($token);

        if (!$user) {
            abort(401);
        }

        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
