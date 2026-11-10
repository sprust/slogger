<?php

declare(strict_types=1);

namespace App\Modules\Auth\Infrastructure\Http\Middlewares;

use App\Modules\Auth\Domain\Actions\FindUserByTokenAction;
use App\Modules\Auth\Domain\Actions\TouchUserTokenAction;
use App\Modules\Auth\Parameters\TokenParameters;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class AuthMiddleware
{
    /**
     * Marks a request this middleware let through, for terminate() to read back.
     *
     * Not $request->user(): the resolver Laravel installs by default goes to a guard, and
     * this application configures none — asking for one on a request that failed here
     * turns a 401 into a 500.
     */
    private const string AUTHENTICATED = 'slogger.authenticated';

    public function __construct(
        private FindUserByTokenAction $findUserByTokenAction,
        private TouchUserTokenAction $touchUserTokenAction
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
            abort(code: 401);
        }

        $user = $this->findUserByTokenAction->handle($token);

        if (!$user) {
            abort(code: 401);
        }

        $request->setUserResolver(static fn() => $user);

        $request->attributes->set(
            key: self::AUTHENTICATED,
            value: true,
        );

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

        // Only a request that actually authenticated renews anything. terminate() runs
        // even for one this middleware refused — the framework calls it after handle(),
        // whether handle() returned or aborted — and a token that was rejected has no
        // business moving the row it was rejected against.
        if (
            !$token || !$request->attributes->getBoolean(
                key: self::AUTHENTICATED,
            )
        ) {
            return;
        }

        // Answers false for a session that ended during the request — a logout, most of
        // all, which must not be undone by the renewal of the very request that did it.
        $this->touchUserTokenAction->handle(
            new TokenParameters(
                token: $token,
            )
        );
    }
}
