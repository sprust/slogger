<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Middlewares;

use App\Modules\Auth\Domain\Actions\FindUserByTokenAction;
use App\Modules\User\Domain\Actions\TouchUserTokenAction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class AuthMiddleware
{
    public function __construct(
        private FindUserByTokenAction $findUserByTokenAction,
        private TouchUserTokenAction $touchUserTokenAction,
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

    /**
     * Pushes the session's expiry out, after the response has gone.
     *
     * Here rather than in handle() so that the write does not sit between the request and
     * its answer: it is bookkeeping, and every authenticated request would otherwise pay
     * for it. The window is idle time — a session ends when its owner stops using it.
     */
    public function terminate(Request $request, Response $response): void
    {
        $token = $request->bearerToken();

        if (!$token) {
            return;
        }

        // Answers false for a session that ended during the request — a logout, most of
        // all, which must not be undone by the renewal of the very request that did it.
        $this->touchUserTokenAction->handle($token);
    }
}
