<?php

namespace SLoggerLaravel\Injectors;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use SLoggerLaravel\Events\RequestHandling;
use Symfony\Component\HttpFoundation\Response;

readonly class SLoggerMiddleware
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
            new RequestHandling($request, $parentTraceId)
        );

        return $next($request);
    }
}
