<?php

declare(strict_types=1);

namespace App\Services\Clickhouse;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Turns a PHP value into the text of a `param_<name>` of the HTTP interface.
 *
 * ClickHouse parses a parameter with the text deserializer of the type named in the
 * query (`{name:Type}`): a top-level string is read in the escaped (TSV) format, so a
 * backslash, a tab and a line break are escaped; an array is a literal, so each string
 * inside it is quoted, with the quote and the backslash escaped. The value never
 * becomes part of the SQL, which is the point of passing it this way.
 */
class ClickhouseParameterFormatter
{
    private const string DATE_TIME_FORMAT = 'Y-m-d H:i:s.u';

    public function format(mixed $value): string
    {
        return match (true) {
            is_null($value)                  => '\N',
            is_bool($value)                  => $value ? 'true' : 'false',
            is_int($value)                   => (string) $value,
            is_float($value)                 => $this->formatFloat($value),
            is_string($value)                => $this->escapeTopLevelString($value),
            $value instanceof DateTimeInterface => $this->formatDateTime($value),
            is_array($value)                 => $this->formatArray($value),
            default                          => throw new InvalidArgumentException(
                sprintf('Unsupported ClickHouse parameter of type [%s]', get_debug_type($value))
            ),
        };
    }

    private function formatFloat(float $value): string
    {
        return json_encode($value, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR);
    }

    private function formatDateTime(DateTimeInterface $value): string
    {
        $utc = new DateTimeZone('UTC');

        return DateTimeImmutable::createFromInterface($value)
            ->setTimezone($utc)
            ->format(self::DATE_TIME_FORMAT);
    }

    private function escapeTopLevelString(string $value): string
    {
        return strtr($value, [
            '\\' => '\\\\',
            "\t" => '\\t',
            "\n" => '\\n',
            "\r" => '\\r',
        ]);
    }

    /**
     * @param array<array-key, mixed> $values
     */
    private function formatArray(array $values): string
    {
        $items = array_map(
            fn(mixed $item): string => $this->formatArrayItem($item),
            array_values($values)
        );

        return '[' . implode(',', $items) . ']';
    }

    private function formatArrayItem(mixed $item): string
    {
        return match (true) {
            is_string($item)                 => $this->quote($item),
            $item instanceof DateTimeInterface => $this->quote($this->formatDateTime($item)),
            is_null($item)                   => 'NULL',
            default                          => $this->format($item),
        };
    }

    private function quote(string $value): string
    {
        return "'" . strtr($value, [
            '\\' => '\\\\',
            "'"  => "\\'",
            "\n" => '\\n',
            "\t" => '\\t',
            "\r" => '\\r',
        ]) . "'";
    }
}
