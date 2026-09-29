<?php

namespace Tests\Modules\Mcp\Infrastructure\Http;

use App\Modules\Mcp\Infrastructure\McpServiceProvider;
use App\Modules\Mcp\Infrastructure\Tools\Contracts\McpToolRegistry;
use App\Modules\Mcp\Infrastructure\Tools\SearchTracesTool;
use App\Modules\Mcp\Repositories\McpRepository;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClassConstant;
use Tests\Modules\Mcp\McpFactoryTrait;
use Tests\TestCase;

class McpEndpointHttpTest extends TestCase
{
    use McpFactoryTrait;

    private const string TOKEN = 'valid-token';

    private const array TOOL_NAMES = [
        'get_services',
        'get_trace_time_range',
        'get_trace_facets',
        'search_traces',
        'get_trace_data_fields',
        'aggregate_traces',
        'compare_trace_groups',
        'get_trace_index_status',
        'get_trace_indexes',
        'get_incidents',
        'get_incident_events',
        'get_trace',
        'get_trace_data',
        'get_trace_tree',
        'search_trace_tree',
        'search_slogger_logs',
    ];

    private McpRepository&MockObject $repository;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('mcp.server_name', 'stand');
        config()->set('app.url', 'https://slogger.stand.example.com');

        $this->repository = $this->createMock(McpRepository::class);

        $this->repository->method('findOneByToken')->willReturnCallback(
            fn(string $token) => match ($token) {
                self::TOKEN => $this->mcpObject(id: 5, token: self::TOKEN),
                'disabled'  => $this->mcpObject(enabled: false, token: 'disabled'),
                default     => null,
            }
        );

        $this->app->instance(McpRepository::class, $this->repository);
        $this->app->instance(
            McpToolRegistry::class,
            new McpToolRegistry([new FakeMcpTool('get_thing'), new FakeMcpTool('get_other')])
        );
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testDiscover(): void
    {
        $this->rpc('server/discover')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('jsonrpc', '2.0')
            ->assertJsonPath('id', 1)
            ->assertJsonPath('result.resultType', 'complete')
            ->assertJsonPath('result.supportedVersions', ['2026-07-28'])
            ->assertJsonPath('result.cacheScope', 'private')
            ->assertJsonPath('result.ttlMs', 3_600_000);

        $body = (string) $this->rpc('server/discover')->getContent();

        $serverInfo = json_decode($body, true)['result']['_meta']['io.modelcontextprotocol/serverInfo'];

        $this->assertSame('slogger-stand', $serverInfo['name']);
        $this->assertSame('SLogger (stand)', $serverInfo['title']);

        $this->assertStringContainsString('"capabilities":{"tools":{},"prompts":{}}', $body);

        $instructions = (string) json_decode($body, true)['result']['instructions'];

        $this->assertStringStartsWith(
            'This is the SLogger installation "stand" at https://slogger.stand.example.com.',
            $instructions
        );
    }

    public function testToolsListIsStable(): void
    {
        $first  = $this->rpc('tools/list')->assertOk();
        $second = $this->rpc('tools/list')->assertOk();

        $first->assertJsonPath('result.tools.0.name', 'get_thing')
            ->assertJsonPath('result.tools.1.name', 'get_other')
            ->assertJsonPath('result.tools.0.annotations.readOnlyHint', true)
            ->assertJsonPath('result.tools.0.inputSchema.required', ['thing_id'])
            ->assertJsonPath('result.cacheScope', 'private');

        $this->assertSame($first->json('result.tools'), $second->json('result.tools'));
    }

    public function testToolCall(): void
    {
        $response = $this->rpc('tools/call', ['name' => 'get_thing', 'arguments' => ['thing_id' => 'a1']])
            ->assertOk()
            ->assertJsonPath('result.isError', false)
            ->assertJsonPath('result.content.0.type', 'text')
            ->assertJsonPath('result.structuredContent.installation', 'stand')
            ->assertJsonPath('result.structuredContent.thing_id', 'a1');

        $this->assertSame(
            $response->json('result.structuredContent'),
            json_decode((string) $response->json('result.content.0.text'), true)
        );
        $this->assertStringEndsWith(
            '…[truncated, 600 chars total]',
            (string) $response->json('result.structuredContent.long')
        );
    }

    public function testToolErrorIsAResult(): void
    {
        $this->rpc('tools/call', ['name' => 'get_thing', 'arguments' => ['thing_id' => 'missing']])
            ->assertOk()
            ->assertJsonPath('result.isError', true)
            ->assertJsonPath('result.structuredContent.error', 'not_found')
            ->assertJsonMissingPath('error');
    }

    public function testUnknownToolIsInvalidParams(): void
    {
        $this->rpc('tools/call', ['name' => 'drop_everything', 'arguments' => []])
            ->assertOk()
            ->assertJsonPath('error.code', -32602);
    }

    public function testArgumentsAreValidated(): void
    {
        $response = $this->rpc('tools/call', ['name' => 'get_thing', 'arguments' => []])
            ->assertOk()
            ->assertJsonPath('error.code', -32602);

        $this->assertStringContainsString('thing_id', (string) $response->json('error.message'));

        $this->rpc('tools/call', ['name' => 'get_thing', 'arguments' => ['thing_id' => 'a', 'extra' => 1]])
            ->assertJsonPath('error.code', -32602);
    }

    public function testToolsListOrder(): void
    {
        /** @var class-string[] $tools */
        $tools = new ReflectionClassConstant(McpServiceProvider::class, 'TOOLS')->getValue();

        $this->app->instance(
            McpToolRegistry::class,
            new McpToolRegistry(array_map(fn(string $tool) => $this->app->make($tool), $tools))
        );

        $this->assertSame(
            self::TOOL_NAMES,
            array_column((array) $this->rpc('tools/list')->assertOk()->json('result.tools'), 'name')
        );
    }

    public function testTraceQueryArgumentsAreValidated(): void
    {
        $this->app->instance(
            McpToolRegistry::class,
            new McpToolRegistry([$this->app->make(SearchTracesTool::class)])
        );

        $response = $this->rpc('tools/call', ['name' => 'search_traces', 'arguments' => ['to' => '2026-09-28T12:00:00Z']])
            ->assertOk()
            ->assertJsonPath('error.code', -32602);

        $this->assertStringContainsString('from', (string) $response->json('error.message'));

        $response = $this->rpc(
            'tools/call',
            [
                'name'      => 'search_traces',
                'arguments' => ['from' => '2026-09-28T10:00:00Z', 'to' => '2026-09-28T12:00:00Z', 'duration_from' => 'abc'],
            ]
        )
            ->assertOk()
            ->assertJsonPath('error.code', -32602);

        $this->assertStringContainsString('duration_from', (string) $response->json('error.message'));

        $this->rpc('tools/list')
            ->assertOk()
            ->assertJsonPath('result.tools.0.name', 'search_traces')
            ->assertJsonPath('result.tools.0.annotations.readOnlyHint', true)
            ->assertJsonPath('result.tools.0.inputSchema.properties.duration_from.type', 'number');
    }

    public function testPrompts(): void
    {
        $response = $this->rpc('prompts/list')
            ->assertOk()
            ->assertJsonPath('result.cacheScope', 'private');

        $this->assertSame(
            ['investigate_errors', 'investigate_latency', 'explain_incident', 'explain_trace'],
            array_column((array) $response->json('result.prompts'), 'name')
        );
        $this->assertSame(
            [['type', false]],
            array_map(
                static fn(array $argument) => [$argument['name'], $argument['required']],
                array_values(array_filter(
                    (array) $response->json('result.prompts.1.arguments'),
                    static fn(array $argument) => $argument['name'] === 'type'
                ))
            )
        );

        $this->rpc('prompts/get', ['name' => 'drop_everything'])
            ->assertJsonPath('error.code', -32602);

        $this->rpc('prompts/get', ['name' => 'explain_trace', 'arguments' => []])
            ->assertJsonPath('error.code', -32602);

        $text = (string) $this->rpc(
            'prompts/get',
            ['name' => 'investigate_errors', 'arguments' => ['service' => 'pms', 'period' => 'last 3 hours']]
        )
            ->assertOk()
            ->assertJsonPath('result.messages.0.role', 'user')
            ->json('result.messages.0.content.text');

        $this->assertStringContainsString('"pms"', $text);
        $this->assertStringContainsString('last 3 hours', $text);
    }

    public function testUnknownMethodIsNotFound(): void
    {
        $this->rpc('resources/list')->assertNotFound()->assertJsonPath('error.code', -32601);
        $this->rpc('ping')->assertNotFound()->assertJsonPath('error.code', -32601);
    }

    public function testLegacyInitializeIsRejectedWithTheSupportedVersion(): void
    {
        $response = $this->call(
            'POST',
            '/mcp',
            server: $this->server(['Authorization' => 'Bearer ' . self::TOKEN]),
            content: '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-11-25"}}'
        );

        $response->assertBadRequest()->assertJsonPath('error.code', -32020);

        $this->assertStringContainsString('2026-07-28', (string) $response->json('error.message'));
    }

    public function testUnsupportedVersion(): void
    {
        $this->rpc('tools/list', version: '2025-11-25')
            ->assertBadRequest()
            ->assertJsonPath('error.code', -32022)
            ->assertJsonPath('error.data.supported', ['2026-07-28'])
            ->assertJsonPath('error.data.requested', '2025-11-25');
    }

    public function testNotificationIsAccepted(): void
    {
        $response = $this->call(
            'POST',
            '/mcp',
            server: $this->server(['Authorization' => 'Bearer ' . self::TOKEN]),
            content: '{"jsonrpc":"2.0","method":"notifications/initialized"}'
        );

        $response->assertStatus(202);

        $this->assertSame('', $response->getContent());
    }

    public function testParseErrors(): void
    {
        $this->call('POST', '/mcp', server: $this->server(['Authorization' => 'Bearer ' . self::TOKEN]), content: '{')
            ->assertBadRequest()
            ->assertJsonPath('error.code', -32700)
            ->assertJsonPath('id', null);

        $this->call('POST', '/mcp', server: $this->server(['Authorization' => 'Bearer ' . self::TOKEN]), content: '[]')
            ->assertBadRequest()
            ->assertJsonPath('error.code', -32600);
    }

    public function testGetAndDeleteAreNotAllowed(): void
    {
        $this->get('/mcp')->assertStatus(405);
        $this->delete('/mcp')->assertStatus(405);
    }

    public function testSessionHeaderIsIgnored(): void
    {
        $response = $this->rpc('tools/list', headers: ['Mcp-Session-Id' => 'abc'])->assertOk();

        $this->assertFalse($response->headers->has('Mcp-Session-Id'));
    }

    public function testTokenIsRequired(): void
    {
        $this->rpc('tools/list', token: null)->assertUnauthorized();
        $this->rpc('tools/list', token: 'unknown')->assertUnauthorized();
        $this->rpc('tools/list', token: 'disabled')->assertUnauthorized();
    }

    public function testForeignOriginIsForbidden(): void
    {
        $this->rpc('tools/list', headers: ['Origin' => 'https://evil.example'])->assertForbidden();
        $this->rpc('tools/list', headers: ['Origin' => 'https://slogger.stand.example.com'])->assertOk();
    }

    public function testRequestIsCounted(): void
    {
        Carbon::setTestNow('2026-09-28 12:00:00');

        $this->repository->expects($this->once())
            ->method('incrementRequestsCount')
            ->with(5, $this->callback(static fn(Carbon $at): bool => $at->eq(Carbon::now())));

        $this->rpc('tools/list')->assertOk();
    }

    /**
     * @param array<string, mixed>  $params
     * @param array<string, string> $headers
     */
    private function rpc(
        string $method,
        array $params = [],
        string $version = '2026-07-28',
        ?string $token = self::TOKEN,
        array $headers = []
    ): TestResponse {
        $params['_meta'] = [
            'io.modelcontextprotocol/protocolVersion' => $version,
            'io.modelcontextprotocol/clientInfo'      => ['name' => 'test', 'version' => '1'],
        ];

        $requestHeaders = [
            'MCP-Protocol-Version' => $version,
            'Mcp-Method'           => $method,
            ...$headers,
        ];

        if (isset($params['name']) && is_string($params['name'])) {
            $requestHeaders['Mcp-Name'] = $params['name'];
        }

        if (!is_null($token)) {
            $requestHeaders['Authorization'] = "Bearer $token";
        }

        return $this->call(
            'POST',
            '/mcp',
            server: $this->server($requestHeaders),
            content: (string) json_encode(['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params])
        );
    }

    /**
     * @param array<string, string> $headers
     *
     * @return array<string, string>
     */
    private function server(array $headers): array
    {
        $server = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT'  => 'application/json, text/event-stream',
        ];

        foreach ($headers as $name => $value) {
            $server['HTTP_' . strtoupper(str_replace('-', '_', $name))] = $value;
        }

        return $server;
    }
}
