<?php

namespace SLoggerLaravel\Guzzle;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use SLoggerLaravel\RequestPreparer\RequestDataFormatters;
use SLoggerLaravel\State;
use SLoggerLaravel\Watchers\Children\HttpClientWatcher;
use Throwable;

readonly class GuzzleHandlerFactory
{
    public function __construct(
        private State $loggerState,
        private HttpClientWatcher $httpClientWatcher
    ) {
    }

    public function prepareHandler(
        RequestDataFormatters $formatters,
        ?HandlerStack $handlerStack = null
    ): HandlerStack {
        $handlersStack = $handlerStack ?: HandlerStack::create();

        if ($this->loggerState->isWatcherEnabled(HttpClientWatcher::class)) {
            $handlersStack->push($this->request());
            $handlersStack->push($this->response($formatters));
        }

        return $handlersStack;
    }

    private function request(): callable
    {
        return Middleware::mapRequest(function (RequestInterface $request): RequestInterface {
            return $this->httpClientWatcher->handleRequest($request);
        });
    }

    private function response(RequestDataFormatters $formatters): callable
    {
        return Middleware::tap(
            after: function (
                RequestInterface $request,
                array $options,
                PromiseInterface $response
            ) use ($formatters): void {
                /** @var Response $responseWaited */

                try {
                    $responseWaited = $response->wait();
                } catch (Throwable $exception) {
                    $this->httpClientWatcher->handleInvalidResponse(
                        $request,
                        $exception,
                        $formatters
                    );

                    return;
                }

                $this->httpClientWatcher->handleResponse(
                    $request,
                    $options,
                    $responseWaited,
                    $formatters
                );
            }
        );
    }
}
