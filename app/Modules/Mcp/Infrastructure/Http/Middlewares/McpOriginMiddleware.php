<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Http\Middlewares;

use App\Modules\Mcp\Domain\Actions\Queries\FindMcpSettingsAction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class McpOriginMiddleware
{
    public function __construct(
        private FindMcpSettingsAction $findMcpSettingsAction
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $origin = $request->headers->get('Origin');

        if (is_null($origin)) {
            return $next($request);
        }

        $appHost    = parse_url($this->findMcpSettingsAction->handle()->appUrl, PHP_URL_HOST);
        $originHost = parse_url($origin, PHP_URL_HOST);

        if (!is_string($originHost) || !is_string($appHost) || strcasecmp($originHost, $appHost) !== 0) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
