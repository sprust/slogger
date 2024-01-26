<?php

namespace SLoggerLaravel\Injectors;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use SLoggerLaravel\SLoggerProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\TerminableInterface;
use Throwable;

readonly class SLoggerMiddleware implements TerminableInterface
{
    public function __construct(
        private Application $app,
        private SLoggerProcessor $processor
    ) {
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $parentTraceId = $request->header('x-parent-trace-id');

        $requestStartedAt = $this->app[Kernel::class]->requestStartedAt();

        try {
            $this->processor->start(
                name: 'request',
                parentTraceId: $parentTraceId,
                loggedAt: $requestStartedAt?->clone()->subMicrosecond()
            );
        } catch (Throwable $exception) {
            // TODO: fire an event
            report($exception);
        }

        return $next($request);
    }

    public function terminate(\Symfony\Component\HttpFoundation\Request $request, Response $response)
    {
        if ($this->processor->isActive()) {
            $this->processor->stop();
        }
    }
}
