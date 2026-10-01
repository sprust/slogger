<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Services;

use Illuminate\Support\Carbon;
use JsonException;

/**
 * Reads the columns of a traces row as ClickHouse hands them over in JSONEachRow.
 */
class ClickhouseTraceRowReader
{
    private const string TIME_FORMAT = 'Y-m-d H:i:s.u';

    private const int DATA_MAX_DEPTH = 1024;

    /**
     * @param array<string, mixed> $row
     */
    public function serviceId(array $row): ?int
    {
        $serviceId = (int) ($row['sid'] ?? 0);

        return $serviceId === 0 ? null : $serviceId;
    }

    /**
     * The column holds '' for a root trace.
     *
     * @param array<string, mixed> $row
     */
    public function parentTraceId(array $row): ?string
    {
        $parentTraceId = (string) ($row['ptid'] ?? '');

        return $parentTraceId === '' ? null : $parentTraceId;
    }

    /**
     * @param array<string, mixed> $row
     *
     * @return string[]
     */
    public function tags(array $row): array
    {
        return array_map(
            static fn(mixed $tag): string => (string) $tag,
            is_array($row['tgs'] ?? null) ? $row['tgs'] : []
        );
    }

    public function float(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    public function time(mixed $value): Carbon
    {
        /** @var Carbon $time */
        $time = Carbon::createFromFormat(self::TIME_FORMAT, (string) $value, 'UTC');

        return $time;
    }

    /**
     * The data as the client sent it: objects keep their key order.
     *
     * @return array<array-key, mixed>|string|bool|int|float|null
     */
    public function data(string $raw): array|string|bool|int|float|null
    {
        if ($raw === '') {
            return [];
        }

        try {
            /** @var array<array-key, mixed>|string|bool|int|float|null $data */
            $data = json_decode($raw, true, self::DATA_MAX_DEPTH, JSON_THROW_ON_ERROR | JSON_BIGINT_AS_STRING);
        } catch (JsonException) {
            return $raw;
        }

        return $data;
    }
}
