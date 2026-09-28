<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Http\Middlewares;

use App\Modules\Mcp\Domain\Actions\Mutations\TouchMcpAction;
use App\Modules\Mcp\Domain\Actions\Queries\FindMcpByTokenAction;
use App\Modules\Mcp\Entities\McpObject;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

readonly class McpTokenMiddleware
{
    public const string ATTRIBUTE = 'mcp';

    public function __construct(
        private FindMcpByTokenAction $findMcpByTokenAction,
        private TouchMcpAction $touchMcpAction
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $mcp = $this->findMcpByTokenAction->handle($token);

        if (is_null($mcp)) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set(self::ATTRIBUTE, $mcp);

        return $next($request);
    }

    public function terminate(Request $request, Response $response): void
    {
        $mcp = $request->attributes->get(self::ATTRIBUTE);

        if (!$mcp instanceof McpObject) {
            return;
        }

        $this->touchMcpAction->handle($mcp);
    }
}
