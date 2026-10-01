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
 * data of the last day is asked, on a sample.
 *
 * Asked in the background (RefreshTraceDataPathTypesTask) and only read here: the sample
 * takes a while, and the request of whoever happens to filter first after the map
 * expired used to pay for it. Until the first refresh there is no map, and a path is
 * read as running through objects only.
 *
 * Only the top level: the JSON column reports the paths inside an array of objects as
 * a type of the array, not as paths of their own, so an array nested in another array
 * is not seen here and is not walked into by the filters.
 */
class ClickhouseDataPathTypes
{
    private const string CACHE_KEY = 'trace:data-array-paths';

    /**
     * Several refreshes long, so that a pool restarting or a refresh failing leaves the
     * last map in use rather than none.
     */
    private const int CACHE_TTL_SECONDS = 1800;

    private const int SAMPLE_ROWS = 100_000;

    public function __construct(
        private readonly ClickhouseClient $client,
        private readonly CacheRepository $cache,
    ) {
    }

    /**
     * The paths as the last refresh found them; empty before the first one.
     *
     * @return string[] paths such as `items` or `request.files`
     */
    public function arrayPaths(): array
    {
        $paths = $this->cache->get(self::CACHE_KEY);

        return is_array($paths) ? array_values(array_map('strval', $paths)) : [];
    }

    /**
     * Reads the paths from the data and stores them for arrayPaths().
     *
     * @return string[] the paths found
     *
     * @throws ClickhouseQueryException
     */
    public function refresh(): array
    {
        $paths = $this->readArrayPaths();

        $this->cache->put(
            key: self::CACHE_KEY,
            value: $paths,
            ttl: self::CACHE_TTL_SECONDS
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
