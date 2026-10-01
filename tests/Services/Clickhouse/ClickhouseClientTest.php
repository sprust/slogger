<?php

declare(strict_types=1);

namespace Tests\Services\Clickhouse;

use App\Services\Clickhouse\ClickhouseClient;
use App\Services\Clickhouse\ClickhouseConnectionConfig;
use App\Services\Clickhouse\ClickhouseParameterFormatter;
use App\Services\Clickhouse\ClickhouseQueryException;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\PumpStream;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ClickhouseClientTest extends TestCase
{
    private ?RequestInterface $sentRequest = null;

    public function testSelectSendsSqlInBodyAndValuesAsParameters(): void
    {
        $client = $this->makeClient(new Response(200, body: ''));

        $client->select(
            sql: 'SELECT tid FROM traces WHERE sid IN {sids:Array(UInt32)} AND tp = {type:String}',
            params: [
                'sids' => [1, 2],
                'type' => "it's",
            ],
            queryIdPrefix: 'trace-list',
            settings: ['max_execution_time' => 30]
        );

        $request = $this->sentRequest;

        self::assertNotNull($request);
        self::assertSame('POST', $request->getMethod());
        self::assertSame('slogger', $request->getHeaderLine('X-ClickHouse-User'));
        self::assertSame('secret', $request->getHeaderLine('X-ClickHouse-Key'));
        self::assertSame(
            'SELECT tid FROM traces WHERE sid IN {sids:Array(UInt32)} AND tp = {type:String}',
            (string) $request->getBody()
        );
        self::assertSame('clickhouse', $request->getUri()->getHost());
        self::assertSame(8123, $request->getUri()->getPort());

        parse_str($request->getUri()->getQuery(), $query);

        self::assertSame('slogger', $query['database']);
        self::assertSame('JSONEachRow', $query['default_format']);
        self::assertSame('30', $query['max_execution_time']);
        self::assertSame('[1,2]', $query['param_sids']);
        self::assertSame("it's", $query['param_type']);
        self::assertMatchesRegularExpression('/^trace-list-[0-9a-f]{16}$/', $query['query_id']);
    }

    public function testSelectDecodesRowsSplitAcrossChunks(): void
    {
        $chunks = ["{\"a\":1,\"b\":\"x\"}\n{\"a\"", ":2,\"b\":\"y\"}\n", '{"a":3,"b":"z"}'];

        $body = new PumpStream(
            static function () use (&$chunks): string|false {
                return array_shift($chunks) ?? false;
            }
        );

        $rows = $this->makeClient(new Response(200, body: $body))->select('SELECT a, b');

        self::assertSame(
            [
                ['a' => 1, 'b' => 'x'],
                ['a' => 2, 'b' => 'y'],
                ['a' => 3, 'b' => 'z'],
            ],
            $rows
        );
    }

    public function testErrorStatusBecomesExceptionWithClickhouseCode(): void
    {
        $client = $this->makeClient(
            new Response(
                404,
                headers: ['X-ClickHouse-Exception-Code' => '60'],
                body: "Code: 60. DB::Exception: Unknown table\n"
            )
        );

        try {
            $client->select('SELECT 1 FROM nothing', queryIdPrefix: 'probe');
            self::fail('No exception');
        } catch (ClickhouseQueryException $exception) {
            self::assertSame(60, $exception->getCode());
            self::assertSame('Code: 60. DB::Exception: Unknown table', $exception->getMessage());
            self::assertStringStartsWith('probe-', (string) $exception->queryId);
        }
    }

    public function testExceptionRowStopsReading(): void
    {
        $client = $this->makeClient(
            new Response(200, body: "{\"a\":1}\n{\"exception\":\"Code: 395. boom\"}\n")
        );

        $this->expectException(ClickhouseQueryException::class);
        $this->expectExceptionMessage('Code: 395. boom');

        $client->select('SELECT a');
    }

    public function testSelectStopsOnTheServerWhenTheClientGivesUp(): void
    {
        $client = $this->makeClient(new Response(200, body: ''));

        $client->select('SELECT 1');

        parse_str((string) $this->sentRequest?->getUri()->getQuery(), $query);

        self::assertSame('60', $query['max_execution_time']);
    }

    public function testCommandSendsStatement(): void
    {
        $client = $this->makeClient(new Response(200, body: ''));

        $client->command('ALTER TABLE traces DROP PARTITION {p:String}', ['p' => '2026-09-29 10:00:00']);

        self::assertSame('ALTER TABLE traces DROP PARTITION {p:String}', (string) $this->sentRequest?->getBody());
    }

    public function remember(RequestInterface $request): void
    {
        $this->sentRequest = $request;
    }

    private function makeClient(ResponseInterface $response): ClickhouseClient
    {
        $httpFactory = new HttpFactory();

        $httpClient = new class($response, $this) implements ClientInterface {
            public function __construct(
                private readonly ResponseInterface $response,
                private readonly ClickhouseClientTest $test
            ) {
            }

            public function sendRequest(RequestInterface $request): ResponseInterface
            {
                $this->test->remember($request);

                return $this->response;
            }
        };

        return new ClickhouseClient(
            httpClient: $httpClient,
            requestFactory: $httpFactory,
            streamFactory: $httpFactory,
            config: new ClickhouseConnectionConfig(
                host: 'clickhouse',
                port: 8123,
                database: 'slogger',
                username: 'slogger',
                password: 'secret',
                timeoutSeconds: 60,
            ),
            parameterFormatter: new ClickhouseParameterFormatter(),
        );
    }
}
