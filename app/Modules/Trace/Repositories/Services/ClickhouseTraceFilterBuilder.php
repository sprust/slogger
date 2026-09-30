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
 * apart.
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
        ?bool $hasProfiling = null,
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

        // Profiling is not stored: nothing has it.
        if ($hasProfiling === true) {
            $conditions[] = '0';
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
     */
    private function makeDataCondition(TraceDataFilterItemParameters $filterItem, array &$params): ?string
    {
        $path = $this->pathResolver->resolve($filterItem->field);

        if (!is_null($filterItem->exists)) {
            $exists = $this->makeExists($path);

            return $filterItem->exists ? $exists : "NOT $exists";
        }

        if (!is_null($filterItem->null)) {
            return $filterItem->null
                ? $this->makeIsNull($path)
                : $this->makeIsNotNull($path);
        }

        if (!is_null($filterItem->numeric)) {
            $value = $this->param($params, (float) $filterItem->numeric->value, 'Float64');

            if ($filterItem->numeric->comp === TraceDataFilterCompNumericTypeEnum::Neq) {
                return 'NOT ' . $this->anyValue($path, fn(string $v) => sprintf('%s = %s', $this->number($v), $value));
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
                    $path,
                    fn(string $v) => sprintf('position(%s, %s) > 0', $this->string($v), $value)
                ),
                TraceDataFilterCompStringTypeEnum::Starts => $this->anyValue(
                    $path,
                    fn(string $v) => sprintf('startsWith(%s, %s)', $this->string($v), $value)
                ),
                TraceDataFilterCompStringTypeEnum::Ends   => $this->anyValue(
                    $path,
                    fn(string $v) => sprintf('endsWith(%s, %s)', $this->string($v), $value)
                ),
                // A trace carrying no such key is not an answer to "not equal" — asking
                // for that is what "does not exist" is for.
                TraceDataFilterCompStringTypeEnum::Neq    => sprintf(
                    '(%s AND NOT %s)',
                    $this->makeExists($path),
                    $this->anyValue($path, $equals)
                ),
            };
        }

        if (!is_null($filterItem->boolean)) {
            $value = $this->param($params, $filterItem->boolean->value, 'Bool');

            return $this->anyValue(
                $path,
                static fn(string $v) => sprintf("dynamicElement(%s, 'Bool') = %s", $v, $value)
            );
        }

        return null;
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
        $exists = sprintf('JSONHas(dt_raw, %s)', $this->rawPath($path));

        if (is_null($path->arrayExpression)) {
            return $exists;
        }

        return sprintf(
            "(%s OR arrayExists(__v -> dynamicType(__v) != 'None', %s))",
            $exists,
            $path->arrayExpression
        );
    }

    private function makeIsNull(TraceDataPathDto $path): string
    {
        $rawPath = $this->rawPath($path);

        return sprintf("(JSONHas(dt_raw, %s) AND JSONType(dt_raw, %s) = 'Null')", $rawPath, $rawPath);
    }

    private function makeIsNotNull(TraceDataPathDto $path): string
    {
        $rawPath = $this->rawPath($path);

        $isNotNull = sprintf("(JSONHas(dt_raw, %s) AND JSONType(dt_raw, %s) != 'Null')", $rawPath, $rawPath);

        if (is_null($path->arrayExpression)) {
            return $isNotNull;
        }

        return sprintf(
            "(%s OR arrayExists(__v -> dynamicType(__v) != 'None', %s))",
            $isNotNull,
            $path->arrayExpression
        );
    }

    /**
     * The keys of the path as the JSON functions take them. The segments are plain names,
     * checked by the resolver, so they are written as literals.
     */
    private function rawPath(TraceDataPathDto $path): string
    {
        return implode(
            ', ',
            array_map(
                static fn(string $segment): string => "'$segment'",
                $path->segments
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
