<?php

declare(strict_types=1);

namespace SConcur\Laravel\Http;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

/**
 * PSR-7 request handler that bridges to the Laravel HTTP kernel.
 *
 * Dev-grade: handles requests against the single application instance, so it is
 * NOT yet coroutine-safe — run with maxConcurrency=1 until the AsyncApplication
 * model lands (see docs/fiber-safe-laravel-bridge.ru.md).
 */
readonly class LaravelHttpHandler
{
    public function __construct(
        private Application $app,
        private HttpFoundationFactory $httpFoundationFactory,
        private PsrHttpFactory $psrHttpFactory,
    ) {
    }

    /**
     * @throws BindingResolutionException
     */
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $laravelRequest = Request::createFromBase(
            $this->httpFoundationFactory->createRequest($request)
        );

        /** @var Kernel $kernel */
        $kernel = $this->app->make(Kernel::class);

        $response = $kernel->handle($laravelRequest);

        $kernel->terminate($laravelRequest, $response);

        return $this->psrHttpFactory->createResponse($response);
    }
}
