<?php

namespace RoadRunner\Servers\Http;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use RoadRunner\Helpers\CurrentApplication;
use RoadRunner\Helpers\DispatchesEvents;
use RoadRunner\Servers\Http\Events\RrHttpPsrRequestHandlingErrorEvent;
use RoadRunner\Servers\Http\Events\RrHttpRequestHandledEvent;
use RoadRunner\Servers\Http\Events\RrHttpRequestHandlingErrorEvent;
use RoadRunner\Servers\Http\Events\RrHttpRequestReceivedEvent;
use RoadRunner\Servers\Http\Events\RrHttpRequestTerminatedEvent;
use RoadRunner\Servers\Http\Events\RrHttpServerErrorEvent;
use RoadRunner\Servers\Http\Events\RrHttpWorkerErrorEvent;
use RoadRunner\Servers\Http\Events\RrHttpWorkerStartingEvent;
use RoadRunner\Servers\Http\Events\RrHttpWorkerStoppingEvent;
use Spiral\RoadRunner\Http\PSR7Worker;
use Spiral\RoadRunner\Worker;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Throwable;

readonly class RrHttpServer
{
    use DispatchesEvents;

    private PSR7Worker $psr7Worker;
    private HttpFoundationFactory $httpFoundationFactory;
    private PsrHttpFactory $psrHttpFactory;

    public function __construct(private Application $app, private Worker $worker)
    {
        $factory = new Psr17Factory();

        $this->psr7Worker = new PSR7Worker($this->worker, $factory, $factory, $factory);

        $this->httpFoundationFactory = new HttpFoundationFactory();
        $this->psrHttpFactory        = new PsrHttpFactory(
            new ServerRequestFactory,
            new StreamFactory,
            new UploadedFileFactory,
            new ResponseFactory
        );
    }

    public function serve(): void
    {
        try {
            $this->onServe();
        } catch (Throwable $exception) {
            $this->dispatchEvent(
                $this->app,
                new RrHttpServerErrorEvent($this->app, $exception)
            );
        }
    }

    private function onServe(): void
    {
        $this->dispatchEvent(
            $this->app,
            new RrHttpWorkerStartingEvent($this->app)
        );

        $handledRequestsCount = 0;
        $maxRequestsCount     = config('roadrunner.http.max_requests_count', 250);

        while (true) {
            try {
                $psr7Request = $this->psr7Worker->waitRequest();

                if ($psr7Request === null) {
                    break;
                }
            } catch (Throwable $exception) {
                $this->dispatchEvent(
                    $this->app,
                    new RrHttpWorkerErrorEvent($exception)
                );

                $this->psr7Worker->respond(new Response(400));

                break;
            }

            try {
                $this->handlePsr7Request($psr7Request);
            } catch (Throwable $exception) {
                $this->dispatchEvent(
                    $this->app,
                    new RrHttpPsrRequestHandlingErrorEvent($this->app, $psr7Request, $exception)
                );

                break;
            }

            ++$handledRequestsCount;

            if ($maxRequestsCount && $maxRequestsCount <= $handledRequestsCount) {
                $this->psr7Worker->getWorker()->stop();
                break;
            }
        }

        $this->dispatchEvent(
            $this->app,
            new RrHttpWorkerStoppingEvent($this->app)
        );
    }

    private function handlePsr7Request(ServerRequestInterface $psr7Request): void
    {
        $request = Request::createFromBase($this->httpFoundationFactory->createRequest($psr7Request));

        $request->enableHttpMethodParameterOverride();

        $app = clone $this->app;

        CurrentApplication::set($app);

        $app['request'] = $request;

        $this->dispatchEvent(
            $app,
            new RrHttpRequestReceivedEvent($app, $request)
        );

        try {
            $kernel = $app->make(Kernel::class);

            $response = $kernel->handle($request);

            $kernel->terminate($request, $response);

            $this->dispatchEvent(
                $app,
                new RrHttpRequestTerminatedEvent($app, $request, $response)
            );

            $psr7Response = $this->psrHttpFactory->createResponse($response);

            $this->psr7Worker->respond($psr7Response);

            $this->dispatchEvent(
                $app,
                new RrHttpRequestHandledEvent($app, $request, $response)
            );

            $route = $request->route();

            if ($route instanceof Route && method_exists($route, 'flushController')) {
                $route->flushController();
            }
        } catch (Throwable $exception) {
            $this->psr7Worker->respond(new Response(500));

            $this->dispatchEvent(
                $app,
                new RrHttpRequestHandlingErrorEvent($app, $request, $exception)
            );
        } finally {
            $app->flush();

            unset($app);

            CurrentApplication::set($this->app);
        }
    }
}
