<?php

namespace Tests\Modules\Auth\Infrastructure\Http\Middlewares;

use App\Modules\Auth\Domain\Actions\FindUserByTokenAction;
use App\Modules\Auth\Entities\LoggedUserObject;
use App\Modules\Auth\Infrastructure\Http\Middlewares\AuthMiddleware;
use App\Modules\User\Domain\Actions\TouchUserTokenAction;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * What renews a session, and what must not.
 *
 * terminate() runs after every request that reached this middleware, including one it
 * refused — the framework calls it whether handle() returned or aborted. Renewing on
 * those would mean a lapsed session that keeps being presented never actually ends.
 */
class AuthMiddlewareTerminateTest extends TestCase
{
    public function testARequestThatDidNotAuthenticateRenewsNothing(): void
    {
        $touch = $this->createMock(TouchUserTokenAction::class);

        $touch->expects($this->never())->method('handle');

        $this
            ->middleware($touch)
            ->terminate(
                request: $this->request('a-token'),
                response: new Response(),
            );
    }

    public function testARequestWithNoTokenRenewsNothing(): void
    {
        $touch = $this->createMock(TouchUserTokenAction::class);

        $touch->expects($this->never())->method('handle');

        $this
            ->middleware($touch)
            ->terminate(
                request: Request::create(
                    uri: '/x',
                ),
                response: new Response(),
            );
    }

    public function testAnAuthenticatedRequestPushesItsSessionOut(): void
    {
        $touch = $this->createMock(TouchUserTokenAction::class);

        $touch->expects($this->once())->method('handle')->with('a-token');

        $request = $this->request('a-token');

        // What handle() leaves behind on a request it let through.
        $request->attributes->set(
            key: 'slogger.authenticated',
            value: true,
        );

        $this
            ->middleware($touch)
            ->terminate(
                request: $request,
                response: new Response(),
            );
    }

    public function testTheTwoHalvesAgreeOnWhatHandleLeftBehind(): void
    {
        $touch = $this->createMock(TouchUserTokenAction::class);

        $touch->expects($this->once())->method('handle')->with('a-token');

        $finder = $this->createMock(FindUserByTokenAction::class);

        $finder
            ->method('handle')
            ->willReturn(
                new LoggedUserObject(
                    id: 1,
                    firstName: 'A',
                    lastName: null,
                    email: 'a@b.c',
                    apiToken: 'a-token',
                )
            );

        $request = $this->request('a-token');
        // Driven end to end rather than by planting the flag: what handle() writes and
        // what terminate() reads are two halves of one agreement, and asserting the name
        // twice would not prove they still meet.
        $middleware = new AuthMiddleware(
            findUserByTokenAction: $finder,
            touchUserTokenAction: new \App\Modules\Auth\Domain\Actions\TouchUserTokenAction($touch),
        );

        $middleware->handle(
            request: $request,
            next: static fn(): Response => new Response(),
        );

        $middleware->terminate(
            request: $request,
            response: new Response(),
        );
    }

    private function middleware(TouchUserTokenAction $touch): AuthMiddleware
    {
        return new AuthMiddleware(
            findUserByTokenAction: $this->createMock(FindUserByTokenAction::class),
            touchUserTokenAction: new \App\Modules\Auth\Domain\Actions\TouchUserTokenAction($touch),
        );
    }

    private function request(string $token): Request
    {
        $request = Request::create(
            uri: '/x',
        );

        $request->headers->set('Authorization', "Bearer {$token}");

        return $request;
    }
}
