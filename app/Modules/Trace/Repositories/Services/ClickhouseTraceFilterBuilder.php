<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterItemParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Repositories\Dto\Trace\TraceDataPathDto;
use App\Modules\Trace\Repositories\Dto\Trace\TraceSqlConditionDto;
use Closure;
use Illuminate\Support\Carbon;
use InvalidArgumentException;

/**
 * The WHERE of a trace query, built from the filters of the traces page and of MCP.
 *
 * Every value goes into a `{name:Type}` placeholder; only the data paths, checked by
 * TraceDataPathResolver, are written into the SQL.
 *
 * A value of the data is read by its own type, the way MongoDB compared it: a number
 * condition matches numbers only (a string "500" is not 500), a string condition
 * strings only. Whether a key is there, and whether it holds null, is read from the
 * data as it arrived (`dt_raw`): the JSON column stores no nulls and cannot tell the two
 * apart. Paths under `cache` and data that is not an object are found by no filter.
 *
 * Holds no state between calls: a data path may be resolved through the cache, which
 * under SConcur hands the process to another request mid-build.
 */
readonly class ClickhouseTraceFilterBuilder
{
    /**
     * The element types the JSON column stores an array of scalars under.
     */
    private const array SCALAR_ARRAY_TYPES = ['String', 'Int64', 'UInt64', 'Float64', 'Bool'];

    // the top-level key the JSON column skips (`SKIP REGEXP '^cache\\.'`)
    private const string CACHE_SEGMENT = 'cache';

    public function __construct(
        private TraceDataPathResolver $pathResolver,
    ) {
    }

    /**
     * @param int[]|null    $serviceIds
     * @param string[]|null $traceIds
     * @param string[]      $types
     * @param string[]      $tags
     * @param string[]      $statuses
     *
     * @throws InvalidArgumentException
     */
    public function build(
        ?array $serviceIds = null,
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $tags = [],
        array $statuses = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterParameters $data = null,
    ): TraceSqlConditionDto {
        /** @var array<string, mixed> $params */
        $params = [];

        $conditions = [];

        if ($serviceIds) {
            $conditions[] = sprintf('sid IN %s', $this->param($params, array_values($serviceIds), 'Array(UInt32)'));
        }

        if ($traceIds) {
            $conditions[] = sprintf('tid IN %s', $this->param($params, array_values($traceIds), 'Array(String)'));
        }

        if ($loggedAtFrom) {
            $conditions[] = sprintf('lat >= %s', $this->param($params, $loggedAtFrom, "DateTime64(6, 'UTC')"));
        }

        if ($loggedAtTo) {
            $conditions[] = sprintf('lat <= %s', $this->param($params, $loggedAtTo, "DateTime64(6, 'UTC')"));
        }

        if ($types) {
            $conditions[] = sprintf('tp IN %s', $this->param($params, array_values($types), 'Array(String)'));
        }

        if ($tags) {
            $conditions[] = sprintf('hasAll(tgs, %s)', $this->param($params, array_values($tags), 'Array(String)'));
        }

        if ($statuses) {
            $conditions[] = sprintf('st IN %s', $this->param($params, array_values($statuses), 'Array(String)'));
        }

        foreach (['dur' => [$durationFrom, $durationTo], 'mem' => [$memoryFrom, $memoryTo], 'cpu' => [$cpuFrom, $cpuTo]] as $column => [$from, $to]) {
            if (!is_null($from)) {
                $conditions[] = sprintf('%s >= %s', $column, $this->param($params, $from, 'Float64'));
            }

            if (!is_null($to)) {
                $conditions[] = sprintf('%s <= %s', $column, $this->param($params, $to, 'Float64'));
            }
        }

        foreach ($data === null ? [] : $data->filter as $filterItem) {
            $condition = $this->makeDataCondition($filterItem, $params);

            if (!is_null($condition)) {
                $conditions[] = $condition;
            }
        }

        return new TraceSqlConditionDto(
            sql: $conditions === [] ? '1' : implode(' AND ', $conditions),
            params: $params
        );
    }

    /**
     * @param array<string, mixed> $params
     *
     * @throws InvalidArgumentException
     */
    private function makeDataCondition(TraceDataFilterItemParameters $filterItem, array &$params): ?string
    {
        $path = $this->pathResolver->resolveField($filterItem->field);

        if ($this->isEmpty($filterItem)) {
            return null;
        }

        // The JSON column skips the cache entries, and a filter finds none of them, negated or not.
        if ($path->segments[0] === self::CACHE_SEGMENT) {
            return '0';
        }

        if (!is_null($filterItem->exists)) {
            $exists = $this->makeExists($path);

            return $this->onObject($filterItem->exists ? $exists : "NOT $exists");
        }

        if (!is_null($filterItem->null)) {
            return $this->onObject(
                $filterItem->null
                    ? $this->makeIsNull($path)
                    : $this->makeIsNotNull($path)
            );
        }

        if (!is_null($filterItem->numeric)) {
            $value = $this->param($params, (float) $filterItem->numeric->value, 'Float64');

            if ($filterItem->numeric->comp === TraceDataFilterCompNumericTypeEnum::Neq) {
                return $this->onObject(
                    'NOT ' . $this->anyValue($path, fn(string $v) => sprintf('%s = %s', $this->number($v), $value))
                );
            }

            $operator = match ($filterItem->numeric->comp) {
                TraceDataFilterCompNumericTypeEnum::Eq  => '=',
                TraceDataFilterCompNumericTypeEnum::Gt  => '>',
                TraceDataFilterCompNumericTypeEnum::Gte => '>=',
                TraceDataFilterCompNumericTypeEnum::Lt  => '<',
                TraceDataFilterCompNumericTypeEnum::Lte => '<=',
            };

            return $this->anyValue($path, fn(string $v) => sprintf('%s %s %s', $this->number($v), $operator, $value));
        }

        if (!is_null($filterItem->string)) {
            $value = $this->param($params, $filterItem->string->value ?? '', 'String');

            $equals = fn(string $v) => sprintf('%s = %s', $this->string($v), $value);

            return match ($filterItem->string->comp) {
                TraceDataFilterCompStringTypeEnum::Eq     => $this->anyValue($path, $equals),
                TraceDataFilterCompStringTypeEnum::Con    => $this->anyValue(
                    path: $path,
                    condition: fn(string $v) => sprintf('position(%s, %s) > 0', $this->string($v), $value)
                ),
                TraceDataFilterCompStringTypeEnum::Starts => $this->anyValue(
                    path: $path,
                    condition: fn(string $v) => sprintf('startsWith(%s, %s)', $this->string($v), $value)
                ),
                TraceDataFilterCompStringTypeEnum::Ends   => $this->anyValue(
                    path: $path,
                    condition: fn(string $v) => sprintf('endsWith(%s, %s)', $this->string($v), $value)
                ),
                // A trace carrying no such key is not an answer to "not equal" — asking
                // for that is what "does not exist" is for.
                TraceDataFilterCompStringTypeEnum::Neq    => $this->onObject(
                    sprintf(
                        '%s AND NOT %s',
                        $this->makeExists($path),
                        $this->anyValue($path, $equals)
                    )
                ),
            };
        }

        if (!is_null($filterItem->boolean)) {
            $value = $this->param($params, $filterItem->boolean->value, 'Bool');

            return $this->anyValue(
                path: $path,
                condition: static fn(string $v) => sprintf("dynamicElement(%s, 'Bool') = %s", $v, $value)
            );
        }

        return null;
    }

    private function isEmpty(TraceDataFilterItemParameters $filterItem): bool
    {
        return is_null($filterItem->exists)
            && is_null($filterItem->null)
            && is_null($filterItem->numeric)
            && is_null($filterItem->string)
            && is_null($filterItem->boolean);
    }

    /**
     * Data that is not an object is found by no data filter, a negated one included.
     */
    private function onObject(string $condition): string
    {
        return sprintf("(JSONType(dt_raw) = 'Object' AND %s)", $condition);
    }

    /**
     * The condition holds for the value at the path, for any element when that value is an
     * array of scalars, or, when a part of the path is an array of objects, for any of its
     * elements — MongoDB's reading of a path.
     *
     * @param Closure(string): string $condition
     */
    private function anyValue(TraceDataPathDto $path, Closure $condition): string
    {
        $conditions = [
            sprintf('coalesce(%s, 0)', $condition($path->objectExpression)),
            sprintf(
                'arrayExists(__e -> coalesce(%s, 0), %s)',
                $condition('__e'),
                $this->scalarArrayElements($path->objectExpression)
            ),
        ];

        if (!is_null($path->arrayExpression)) {
            $conditions[] = sprintf(
                'arrayExists(__v -> coalesce(%s, 0), %s)',
                $condition('__v'),
                $path->arrayExpression
            );
        }

        return '(' . implode(' OR ', $conditions) . ')';
    }

    /**
     * The elements of the value when it is an array of scalars, as values of their own
     * type; an empty array when it is anything else.
     *
     * The JSON column stores such an array under the type its elements share —
     * `Array(Nullable(String))`, `Array(Nullable(Int64))` and so on — or as
     * `Array(Dynamic)` when they differ. Each of those is read and brought to
     * `Array(Dynamic)`, so the condition sees every element by its own type.
     */
    private function scalarArrayElements(string $value): string
    {
        $arrays = array_map(
            static fn(string $type): string => sprintf(
                "CAST(dynamicElement(%s, 'Array(Nullable(%s))'), 'Array(Dynamic)')",
                $value,
                $type
            ),
            self::SCALAR_ARRAY_TYPES
        );

        $arrays[] = sprintf("dynamicElement(%s, 'Array(Dynamic)')", $value);

        return sprintf('arrayConcat(%s)', implode(', ', $arrays));
    }

    private function makeExists(TraceDataPathDto $path): string
    {
        return $this->rawValue(
            path: $path,
            condition: static fn(string $json, string $keys): string => sprintf('JSONHas(%s, %s)', $json, $keys)
        );
    }

    private function makeIsNull(TraceDataPathDto $path): string
    {
        return $this->rawValue(
            path: $path,
            condition: static fn(string $json, string $keys): string => sprintf(
                "(JSONHas(%s, %s) AND JSONType(%s, %s) = 'Null')",
                $json,
                $keys,
                $json,
                $keys
            )
        );
    }

    private function makeIsNotNull(TraceDataPathDto $path): string
    {
        return $this->rawValue(
            path: $path,
            condition: static fn(string $json, string $keys): string => sprintf(
                "(JSONHas(%s, %s) AND JSONType(%s, %s) != 'Null')",
                $json,
                $keys,
                $json,
                $keys
            )
        );
    }

    /**
     * The condition on the data as it arrived, which keeps the nulls the JSON column drops: on
     * the value at the path, or, when a part of the path is an array of objects, on any of
     * its elements. Only that one level of arrays is walked into.
     *
     * @param Closure(string, string): string $condition takes the JSON and the keys inside it
     */
    private function rawValue(TraceDataPathDto $path, Closure $condition): string
    {
        $value = $condition('dt_raw', $this->rawKeys($path->segments));

        if ($path->arraySegments === []) {
            return $value;
        }

        return sprintf(
            '(%s OR arrayExists(__e -> %s, JSONExtractArrayRaw(dt_raw, %s)))',
            $value,
            $condition('__e', $this->rawKeys(array_slice($path->segments, count($path->arraySegments)))),
            $this->rawKeys($path->arraySegments)
        );
    }

    /**
     * The keys as the JSON functions take them. They are plain names, checked by the
     * resolver, so they are written as literals.
     *
     * @param string[] $segments
     */
    private function rawKeys(array $segments): string
    {
        return implode(
            ', ',
            array_map(
                static fn(string $segment): string => "'$segment'",
                $segments
            )
        );
    }

    private function number(string $value): string
    {
        return sprintf(
            "if(dynamicType(%s) IN ('Int64', 'UInt64', 'Float64'), accurateCastOrNull(%s, 'Float64'), NULL)",
            $value,
            $value
        );
    }

    private function string(string $value): string
    {
        return sprintf("dynamicElement(%s, 'String')", $value);
    }

    /**
     * @param array<string, mixed> $params
     */
    private function param(array &$params, mixed $value, string $type): string
    {
        $name = 'f' . count($params);

        $params[$name] = $value;

        return sprintf('{%s:%s}', $name, $type);
    }
}
