<?php

declare(strict_types=1);

namespace App\Services\SLogger;

use Illuminate\Http\Request;
use SLoggerLaravel\Watchers\Parents\RequestWatcher as BaseRequestWatcher;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tags a request to /mcp with its JSON-RPC method and tool or prompt name, so that MCP traces can be told apart.
 */
class RequestWatcher extends BaseRequestWatcher
{
    private const string MCP_ROUTE_NAME = 'mcp';

    protected function getPostTags(Request $request, Response $response): ?array
    {
        $tags = parent::getPostTags($request, $response);

        // only a successful answer has its body checked against the MCP headers
        if (is_null($tags) || !$request->routeIs(self::MCP_ROUTE_NAME) || !$response->isSuccessful()) {
            return $tags;
        }

        foreach ([$request->json('method'), $request->json('params.name')] as $tag) {
            if (is_string($tag) && $tag !== '') {
                $tags[] = $tag;
            }
        }

        return $tags;
    }
}
