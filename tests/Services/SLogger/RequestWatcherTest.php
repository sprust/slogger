<?php

declare(strict_types=1);

namespace Tests\Services\SLogger;

use App\Services\SLogger\RequestWatcher;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Route;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use SLoggerLaravel\Context\ArrayTraceContext;
use SLoggerLaravel\Processor;

class RequestWatcherTest extends TestCase
{
    public function testAToolCallIsTaggedWithItsMethodAndName(): void
    {
        $this->assertSame(
            ['/mcp', 'tools/call', 'get_services'],
            $this->tags(
                request: $this->request(
                    routeName: 'mcp',
                    uri: 'mcp',
                    body: ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'get_services']]
                ),
                status: 200
            )
        );
    }

    public function testAMethodWithoutANameIsTaggedWithTheMethodOnly(): void
    {
        $this->assertSame(
            ['/mcp', 'tools/list'],
            $this->tags(
                request: $this->request(
                    routeName: 'mcp',
                    uri: 'mcp',
                    body: ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list']
                ),
                status: 200
            )
        );
    }

    public function testARejectedMcpRequestKeepsTheRouteTagOnly(): void
    {
        $this->assertSame(
            ['/mcp'],
            $this->tags(
                request: $this->request(
                    routeName: 'mcp',
                    uri: 'mcp',
                    body: ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'forged']]
                ),
                status: 400
            )
        );
    }

    public function testAnotherRouteKeepsItsTags(): void
    {
        $this->assertSame(
            ['/admin-api/mcps'],
            $this->tags(
                request: $this->request(
                    routeName: 'admin-api.mcps.index',
                    uri: 'admin-api/mcps',
                    body: ['method' => 'tools/list']
                ),
                status: 200
            )
        );
    }

    /**
     * @param array<string, mixed> $body
     */
    private function request(string $routeName, string $uri, array $body): Request
    {
        $request = Request::create(
            uri: "/$uri",
            method: 'POST',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode($body)
        );

        $route = new Route('POST', $uri, []);

        $route->name($routeName);
        $route->bind($request);

        $request->setRouteResolver(static fn(): Route => $route);

        return $request;
    }

    /**
     * @return string[]|null
     */
    private function tags(Request $request, int $status): ?array
    {
        $watcher = new RequestWatcher(
            app: $this->createMock(Application::class),
            processor: $this->createMock(Processor::class),
            context: new ArrayTraceContext()
        );

        return new ReflectionMethod($watcher, 'getPostTags')->invoke($watcher, $request, new Response(status: $status));
    }
}
