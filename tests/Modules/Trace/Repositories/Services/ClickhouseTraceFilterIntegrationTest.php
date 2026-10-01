<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterItemParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterNumericParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterStringParameters;
use App\Modules\Trace\Repositories\Services\ClickhouseDataPathTypes;
use App\Modules\Trace\Repositories\Services\ClickhouseTraceFilterBuilder;
use App\Modules\Trace\Repositories\Services\TraceDataPathResolver;
use App\Services\Clickhouse\ClickhouseClient;
use App\Services\Clickhouse\ClickhouseConnectionConfig;
use App\Services\Clickhouse\ClickhouseParameterFormatter;
use App\Services\Clickhouse\ClickhouseQueryException;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use RuntimeException;
use Tests\TestCase;

/**
 * The data filters run against the ClickHouse of the docker stack, in a database of their
 * own that is dropped afterwards. Skipped when ClickHouse is out of reach.
 */
class ClickhouseTraceFilterIntegrationTest extends TestCase
{
    // the receiver's insert: a string that looks like a date stays a string
    private const string INSERT_SETTINGS = 'input_format_try_infer_dates = 0, input_format_try_infer_datetimes = 0, '
        . 'type_json_skip_duplicated_paths = 1';

    private static ?ClickhouseClient $client = null;

    private static ?ClickhouseClient $serverClient = null;

    private static ?string $database = null;

    private static ?string $skipReason = null;

    public static function tearDownAfterClass(): void
    {
        if (self::$serverClient !== null && self::$database !== null) {
            self::$serverClient->command(sprintf('DROP DATABASE IF EXISTS %s', self::$database));
        }

        self::$client       = null;
        self::$serverClient = null;
        self::$database     = null;
        self::$skipReason   = null;

        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (self::$client === null && self::$skipReason === null) {
            $this->createDatabase();
        }

        if (self::$skipReason !== null) {
            $this->markTestSkipped(self::$skipReason);
        }
    }

    public function testIntegerAndFloatAreTheSameNumber(): void
    {
        $this->insert('num', [
            'int'    => '{"response":{"status":500}}',
            'float'  => '{"response":{"status":500.0}}',
            'string' => '{"response":{"status":"500"}}',
            'less'   => '{"response":{"status":499.5}}',
        ]);

        $this->assertSame(
            ['num-float', 'num-int'],
            $this->find('num', $this->numeric('dt.response.status', 500, TraceDataFilterCompNumericTypeEnum::Gte))
        );
    }

    public function testArrayOfScalarsMatchesAnyElement(): void
    {
        $this->insert('scalars', [
            'array' => '{"roles":["admin","dev"]}',
            'value' => '{"roles":"admin"}',
            'other' => '{"roles":["dev"]}',
        ]);

        $this->assertSame(
            ['scalars-array', 'scalars-value'],
            $this->find('scalars', $this->string('dt.roles', 'admin', TraceDataFilterCompStringTypeEnum::Eq))
        );
    }

    public function testNullAndMissingAreTwoThings(): void
    {
        $this->insert('null', [
            'null'    => '{"user":null}',
            'missing' => '{"other":1}',
            'value'   => '{"user":{"id":1}}',
            'text'    => '"just text"',
            'list'    => '[1,2]',
        ]);

        $this->assertSame(['null-null', 'null-value'], $this->find('null', $this->presence('dt.user', exists: true)));
        $this->assertSame(['null-missing'], $this->find('null', $this->presence('dt.user', exists: false)));
        $this->assertSame(['null-null'], $this->find('null', $this->presence('dt.user', null: true)));
        $this->assertSame(['null-value'], $this->find('null', $this->presence('dt.user', null: false)));
        $this->assertSame(
            ['null-missing', 'null-null', 'null-value'],
            $this->find('null', $this->numeric('dt.user', 5, TraceDataFilterCompNumericTypeEnum::Neq)),
            'data that is not an object is found by no filter, a negated one included'
        );
    }

    public function testNullAndMissingThroughAnArrayOfObjects(): void
    {
        $this->insert('items', [
            'null'    => '{"items":[{"name":"a"},{"price":null}]}',
            'missing' => '{"items":[{"name":"a"}]}',
            'value'   => '{"items":[{"price":10},{"name":"b"}]}',
        ]);

        $this->assertSame(['items-null', 'items-value'], $this->find('items', $this->presence('dt.items.price', exists: true)));
        $this->assertSame(['items-missing'], $this->find('items', $this->presence('dt.items.price', exists: false)));
        $this->assertSame(['items-null'], $this->find('items', $this->presence('dt.items.price', null: true)));
        $this->assertSame(['items-value'], $this->find('items', $this->presence('dt.items.price', null: false)));
        $this->assertSame(
            ['items-value'],
            $this->find('items', $this->numeric('dt.items.price', 10, TraceDataFilterCompNumericTypeEnum::Eq))
        );
    }

    public function testCachePathIsNotFound(): void
    {
        $this->insert('cache', [
            'entry' => '{"type":"set","key":"product_12","cache":{"product_12":{"value":"x"}}}',
            'other' => '{"type":"get"}',
        ]);

        $this->assertSame([], $this->find('cache', $this->string('dt.cache.product_12.value', 'x', TraceDataFilterCompStringTypeEnum::Eq)));
        $this->assertSame([], $this->find('cache', $this->presence('dt.cache.product_12.value', exists: true)));
        $this->assertSame([], $this->find('cache', $this->presence('dt.cache.product_12.value', exists: false)));
        $this->assertSame([], $this->find('cache', $this->string('dt.cache.product_12.value', 'y', TraceDataFilterCompStringTypeEnum::Neq)));
        $this->assertSame(['cache-entry'], $this->find('cache', $this->string('dt.type', 'set', TraceDataFilterCompStringTypeEnum::Eq)));
    }

    public function testContainsTakesTheTextAsItIs(): void
    {
        $this->insert('uri', [
            'dot' => '{"request":{"uri":"/x/a.b/y"}}',
            'any' => '{"request":{"uri":"/x/axb/y"}}',
        ]);

        $this->assertSame(['uri-dot'], $this->find('uri', $this->string('dt.request.uri', 'a.b', TraceDataFilterCompStringTypeEnum::Con)));
    }

    public function testDateLikeStringIsAString(): void
    {
        $this->insert('date', [
            'at'    => '{"at":"2026-09-29 10:00:00"}',
            'other' => '{"at":"2026-09-29 11:00:00"}',
        ]);

        $this->assertSame(
            ['date-at'],
            $this->find('date', $this->string('dt.at', '2026-09-29 10:00:00', TraceDataFilterCompStringTypeEnum::Eq))
        );
    }

    private function createDatabase(): void
    {
        $config = config('database.connections.clickhouse');

        $database = sprintf('slogger_test_filters_%s', bin2hex(random_bytes(4)));

        if ($database === $config['database']) {
            throw new RuntimeException('The test database must not be the one of the application');
        }

        $serverClient = $this->makeClient(config: $config, database: 'system');

        try {
            $serverClient->select('SELECT 1');
        } catch (ClickhouseQueryException $exception) {
            self::$skipReason = sprintf('ClickHouse is out of reach: %s', $exception->getMessage());

            return;
        }

        $serverClient->command(sprintf('CREATE DATABASE %s', $database));

        self::$serverClient = $serverClient;
        self::$database     = $database;

        self::$client = $this->makeClient(config: $config, database: $database);

        // the data columns of the traces table, as its migration makes them
        self::$client->command(
            <<<'SQL'
                CREATE TABLE traces
                (
                    tid    String,
                    dt     JSON(max_dynamic_paths = 256, SKIP REGEXP '^cache\\.'),
                    dt_raw String
                )
                ENGINE = MergeTree
                ORDER BY tid
                SQL
        );
    }

    /**
     * @param array<string, mixed> $config
     */
    private function makeClient(array $config, string $database): ClickhouseClient
    {
        $httpFactory = new HttpFactory();

        return new ClickhouseClient(
            httpClient: new Client(['connect_timeout' => 2, 'timeout' => 20]),
            requestFactory: $httpFactory,
            streamFactory: $httpFactory,
            config: new ClickhouseConnectionConfig(
                host: (string) $config['host'],
                port: (int) $config['port'],
                database: $database,
                username: (string) $config['username'],
                password: (string) $config['password'],
                timeoutSeconds: 20,
            ),
            parameterFormatter: new ClickhouseParameterFormatter(),
        );
    }

    /**
     * Writes the rows the way the receiver does: the data as it came in `dt_raw`, and in
     * `dt` when it is an object.
     *
     * @param array<string, string> $rawDataByName
     */
    private function insert(string $scope, array $rawDataByName): void
    {
        $lines = [];

        foreach ($rawDataByName as $name => $rawData) {
            $isObject = str_starts_with($rawData, '{');

            $lines[] = sprintf(
                '{"tid":%s,"dt":%s,"dt_raw":%s}',
                json_encode("$scope-$name", JSON_THROW_ON_ERROR),
                $isObject ? $rawData : '{}',
                json_encode($rawData, JSON_THROW_ON_ERROR)
            );
        }

        $this->client()->command(
            sprintf("INSERT INTO traces SETTINGS %s FORMAT JSONEachRow\n%s", self::INSERT_SETTINGS, implode("\n", $lines))
        );
    }

    /**
     * @return string[]
     */
    private function find(string $scope, TraceDataFilterItemParameters $filterItem): array
    {
        $pathTypes = $this->createMock(ClickhouseDataPathTypes::class);
        $pathTypes->method('arrayPaths')->willReturn(['items']);

        $condition = new ClickhouseTraceFilterBuilder(new TraceDataPathResolver($pathTypes))->build(
            data: new TraceDataFilterParameters(filter: [$filterItem])
        );

        $rows = $this->client()->select(
            sql: sprintf('SELECT tid FROM traces WHERE startsWith(tid, {scope:String}) AND %s ORDER BY tid', $condition->sql),
            params: [...$condition->params, 'scope' => "$scope-"]
        );

        return array_map(static fn(array $row): string => (string) $row['tid'], $rows);
    }

    private function numeric(string $field, int|float $value, TraceDataFilterCompNumericTypeEnum $comp): TraceDataFilterItemParameters
    {
        return new TraceDataFilterItemParameters(
            field: $field,
            null: null,
            exists: null,
            numeric: new TraceDataFilterNumericParameters(value: $value, comp: $comp),
            string: null,
            boolean: null
        );
    }

    private function string(string $field, string $value, TraceDataFilterCompStringTypeEnum $comp): TraceDataFilterItemParameters
    {
        return new TraceDataFilterItemParameters(
            field: $field,
            null: null,
            exists: null,
            numeric: null,
            string: new TraceDataFilterStringParameters(value: $value, comp: $comp),
            boolean: null
        );
    }

    private function presence(string $field, ?bool $exists = null, ?bool $null = null): TraceDataFilterItemParameters
    {
        return new TraceDataFilterItemParameters(
            field: $field,
            null: $null,
            exists: $exists,
            numeric: null,
            string: null,
            boolean: null
        );
    }

    private function client(): ClickhouseClient
    {
        return self::$client ?? throw new RuntimeException('ClickHouse is not set up');
    }
}
