<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Services;

use App\Services\Clickhouse\ClickhouseClient;
use App\Services\Clickhouse\ClickhouseQueryException;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Which top-level paths of the trace data hold arrays of objects.
 *
 * MongoDB walks into an array by itself: `items.price` matches a trace whose `items`
 * is a list of objects with a price. ClickHouse has to be told — `dt.items[].price` —
 * and the query cannot tell from the path alone which of its parts is the array. So the
 * data of the last day is asked, on a sample and at most once in five minutes.
 *
 * Only the top level: the JSON column reports the paths inside an array of objects as
 * a type of the array, not as paths of their own, so an array nested in another array
 * is not seen here and is not walked into by the filters.
 */
class ClickhouseDataPathTypes
{
    private const string CACHE_KEY = 'trace:data-array-paths';

    private const int CACHE_TTL_SECONDS = 300;

    private const int SAMPLE_ROWS = 100_000;

    public function __construct(
        private readonly ClickhouseClient $client,
        private readonly CacheRepository $cache,
    ) {
    }

    /**
     * @return string[] paths such as `items` or `request.files`
     *
     * @throws ClickhouseQueryException
     */
    public function arrayPaths(): array
    {
        /** @var string[] $paths */
        $paths = $this->cache->remember(
            key: self::CACHE_KEY,
            ttl: self::CACHE_TTL_SECONDS,
            callback: fn(): array => $this->readArrayPaths()
        );

        return $paths;
    }

    /**
     * @return string[]
     *
     * @throws ClickhouseQueryException
     */
    private function readArrayPaths(): array
    {
        $rows = $this->client->select(
            sql: 'SELECT distinctJSONPathsAndTypes(dt) AS paths FROM '
            . '(SELECT dt FROM traces WHERE lat >= now64(6) - INTERVAL 1 DAY LIMIT {sample:UInt32})',
            params: ['sample' => self::SAMPLE_ROWS],
            queryIdPrefix: 'trace-data-paths'
        );

        /** @var array<string, string[]> $pathTypes */
        $pathTypes = is_array($rows[0]['paths'] ?? null) ? $rows[0]['paths'] : [];

        $arrayPaths = [];

        foreach ($pathTypes as $path => $types) {
            foreach ($types as $type) {
                if (str_starts_with($type, 'Array(JSON')) {
                    $arrayPaths[] = (string) $path;

                    break;
                }
            }
        }

        sort($arrayPaths);

        return $arrayPaths;
    }
}
