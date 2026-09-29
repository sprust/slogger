<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Repositories\Dto\Trace\TraceDataPathDto;
use App\Services\Clickhouse\ClickhouseQueryException;
use InvalidArgumentException;

/**
 * Turns a path of the trace data (`request.uri`, `dt.request.uri`) into the SQL that
 * reads it.
 *
 * A path is an identifier of the query, not a value, so it cannot travel as a
 * parameter: it is checked to be names joined by dots before it goes anywhere near SQL.
 */
readonly class TraceDataPathResolver
{
    public const string PATH_PATTERN = '/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)*$/';

    public function __construct(
        private ClickhouseDataPathTypes $pathTypes,
    ) {
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function resolve(string $path): TraceDataPathDto
    {
        $path = str_starts_with($path, 'dt.') ? substr($path, 3) : $path;

        if (!preg_match(self::PATH_PATTERN, $path)) {
            throw new InvalidArgumentException(
                sprintf('Invalid data path [%s]: only letters, digits, _ and dots are allowed', $path)
            );
        }

        $segments = explode('.', $path);

        return new TraceDataPathDto(
            segments: $segments,
            objectExpression: 'dt.' . $this->join($segments),
            arrayExpression: $this->makeArrayExpression($segments)
        );
    }

    /**
     * The first part of the path that holds arrays of objects splits it in two: the array,
     * and the path inside each of its objects.
     *
     * @param string[] $segments
     *
     * @throws ClickhouseQueryException
     */
    private function makeArrayExpression(array $segments): ?string
    {
        if (count($segments) < 2) {
            return null;
        }

        $arrayPaths = $this->pathTypes->arrayPaths();

        for ($length = 1; $length < count($segments); $length++) {
            $prefix = array_slice($segments, 0, $length);

            if (in_array(implode('.', $prefix), $arrayPaths, true)) {
                return sprintf(
                    'dt.%s[].%s',
                    $this->join($prefix),
                    $this->join(array_slice($segments, $length))
                );
            }
        }

        return null;
    }

    /**
     * @param string[] $segments
     */
    private function join(array $segments): string
    {
        return implode(
            '.',
            array_map(
                static fn(string $segment): string => "`$segment`",
                $segments
            )
        );
    }
}
