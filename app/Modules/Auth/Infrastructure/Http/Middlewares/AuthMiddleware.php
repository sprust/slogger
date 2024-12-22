<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Middlewares;

use App\Modules\Auth\Contracts\Actions\FindUserByTokenActionInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class AuthMiddleware
{
    public function __construct(
        private FindUserByTokenActionInterface $findUserByTokenAction
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
