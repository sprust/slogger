<?php

namespace SLoggerLaravel\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use SLoggerLaravel\Events\SLoggerRequestHandling;
use Symfony\Component\HttpFoundation\Response;

readonly class SLoggerHttpMiddleware
{
    public function __construct(private Application $app)
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

        $this->app['events']->dispatch(
            new SLoggerRequestHandling($request, $parentTraceId)
        );

        return $next($request);
    }
}
