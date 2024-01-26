<?php

namespace SLoggerLaravel\Injectors;

use Closure;
use Illuminate\Http\Request;
use SLoggerLaravel\SLoggerProcessor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\TerminableInterface;
use Throwable;

readonly class SLoggerMiddleware implements TerminableInterface
{
    public function __construct(private SLoggerProcessor $processor)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $parentTraceId = $request->header('x-parent-trace-id');

        try {
            $this->processor->start($parentTraceId);
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
