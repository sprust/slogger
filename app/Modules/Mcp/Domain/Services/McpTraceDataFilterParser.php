<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Services;

use App\Modules\Mcp\Domain\Exceptions\McpTraceDataFilterInvalidException;
use App\Modules\Trace\Enums\TraceDataFilterCompNumericTypeEnum;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterBooleanParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterItemParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterNumericParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterStringParameters;

readonly class McpTraceDataFilterParser
{
    public const string FORMAT = '<key> <operator> <value>: "= != > >= < <=" with a number, '
        . '"= !=" with true or false, "= != contains starts ends" with a "double-quoted" string, '
        . 'or "exists", "missing", "is null", "is not null" without a value. '
        . '<key> is names of letters, digits and _ joined by dots; on an array, of objects or of '
        . 'values, a condition holds when any element matches. '
        . 'Examples: response.status >= 500, request.uri contains "/api", user.id exists.';

    // a data path is written into the query, so it is names joined by dots and nothing else
    private const string KEY = '(?<key>[A-Za-z0-9_]+(?:\.[A-Za-z0-9_]+)*)';

    /**
     * @param string[] $conditions
     *
     * @return TraceDataFilterItemParameters[]
     *
     * @throws McpTraceDataFilterInvalidException
     */
    public function parse(array $conditions): array
    {
        return array_map(
            fn(string $condition) => $this->parseCondition($condition),
            $conditions
        );
    }

    /**
     * @throws McpTraceDataFilterInvalidException
     */
    private function parseCondition(string $condition): TraceDataFilterItemParameters
    {
        $value = trim($condition);

        if (preg_match('/^' . self::KEY . '\s+(?<presence>exists|missing|is null|is not null)$/', $value, $matches)) {
            return new TraceDataFilterItemParameters(
                field: $this->field($matches['key']),
                null: match ($matches['presence']) {
                    'is null'     => true,
                    'is not null' => false,
                    default       => null,
                },
                exists: match ($matches['presence']) {
                    'exists'  => true,
                    'missing' => false,
                    default   => null,
                },
                numeric: null,
                string: null,
                boolean: null
            );
        }

        if (preg_match('/^' . self::KEY . '\s*(?<op>=|!=)\s*(?<value>true|false)$/', $value, $matches)) {
            $isTrue = $matches['value'] === 'true';

            return $this->item(
                key: $matches['key'],
                boolean: new TraceDataFilterBooleanParameters($matches['op'] === '=' ? $isTrue : !$isTrue)
            );
        }

        if (preg_match('/^' . self::KEY . '\s*(?<op>>=|<=|!=|=|>|<)\s*(?<value>-?\d+(\.\d+)?)$/', $value, $matches)) {
            return $this->item(
                key: $matches['key'],
                numeric: new TraceDataFilterNumericParameters(
                    value: str_contains($matches['value'], '.') ? (float) $matches['value'] : (int) $matches['value'],
                    comp: TraceDataFilterCompNumericTypeEnum::from($matches['op'])
                )
            );
        }

        if (preg_match(
            '/^' . self::KEY . '\s*(?<op>!=|=|contains|starts|ends)\s*"(?<value>.*)"$/',
            $value,
            $matches
        )) {
            return $this->item(
                key: $matches['key'],
                string: new TraceDataFilterStringParameters(
                    value: $matches['value'],
                    comp: match ($matches['op']) {
                        '='        => TraceDataFilterCompStringTypeEnum::Eq,
                        '!='       => TraceDataFilterCompStringTypeEnum::Neq,
                        'contains' => TraceDataFilterCompStringTypeEnum::Con,
                        'starts'   => TraceDataFilterCompStringTypeEnum::Starts,
                        default    => TraceDataFilterCompStringTypeEnum::Ends,
                    }
                )
            );
        }

        throw new McpTraceDataFilterInvalidException($condition);
    }

    private function item(
        string $key,
        ?TraceDataFilterNumericParameters $numeric = null,
        ?TraceDataFilterStringParameters $string = null,
        ?TraceDataFilterBooleanParameters $boolean = null
    ): TraceDataFilterItemParameters {
        return new TraceDataFilterItemParameters(
            field: $this->field($key),
            null: null,
            exists: null,
            numeric: $numeric,
            string: $string,
            boolean: $boolean
        );
    }

    private function field(string $key): string
    {
        return "dt.$key";
    }
}
