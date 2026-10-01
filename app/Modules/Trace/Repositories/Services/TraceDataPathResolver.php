<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Repositories\Dto\Trace\TraceDataPathDto;
use InvalidArgumentException;

/**
 * Turns a path of the trace data (`request.uri`) into the SQL that reads it.
 *
 * A path is an identifier of the query, not a value, so it cannot travel as a
 * parameter: it is checked to be names joined by dots before it goes anywhere near SQL.
 */
readonly class TraceDataPathResolver
{
    public const string PATH_PATTERN = '/^[A-Za-z0-9_]+(\.[A-Za-z0-9_]+)*\z/';

    // a filter field names the path with the `dt.` of the stored trace in front
    private const string FIELD_PREFIX = 'dt.';

    public function __construct(
        private ClickhouseDataPathTypes $pathTypes,
    ) {
    }

    /**
     * A path of the data as it is, `request.uri`: a top-level key `dt` is a key like any other.
     *
     * @throws InvalidArgumentException
     */
    public function resolve(string $path): TraceDataPathDto
    {
        if (!preg_match(self::PATH_PATTERN, $path)) {
            throw new InvalidArgumentException(
                sprintf('Invalid data path [%s]: only letters, digits, _ and dots are allowed', $path)
            );
        }

        $segments = explode('.', $path);

        $arraySegments = $this->findArraySegments($segments);

        return new TraceDataPathDto(
            segments: $segments,
            objectExpression: 'dt.' . $this->join($segments),
            arrayExpression: $arraySegments === []
                ? null
                : sprintf(
                    'dt.%s[].%s',
                    $this->join($arraySegments),
                    $this->join(array_slice($segments, count($arraySegments)))
                ),
            arraySegments: $arraySegments
        );
    }

    /**
     * A filter field, `dt.request.uri`: the path with the `dt.` the filters put in front of it.
     *
     * @throws InvalidArgumentException
     */
    public function resolveField(string $field): TraceDataPathDto
    {
        if (!str_starts_with($field, self::FIELD_PREFIX)) {
            throw new InvalidArgumentException(
                sprintf('Invalid data field [%s]: it must start with %s', $field, self::FIELD_PREFIX)
            );
        }

        return $this->resolve(
            substr($field, strlen(self::FIELD_PREFIX))
        );
    }

    /**
     * The first part of the path that holds arrays of objects splits it in two: the array,
     * and the path inside each of its objects.
     *
     * @param string[] $segments
     *
     * @return string[]
     */
    private function findArraySegments(array $segments): array
    {
        if (count($segments) < 2) {
            return [];
        }

        $arrayPaths = $this->pathTypes->arrayPaths();

        for ($length = 1; $length < count($segments); $length++) {
            $prefix = array_slice($segments, 0, $length);

            if (in_array(implode('.', $prefix), $arrayPaths, true)) {
                return $prefix;
            }
        }

        return [];
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
