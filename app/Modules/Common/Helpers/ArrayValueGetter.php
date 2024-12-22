<?php

declare(strict_types=1);

namespace App\Modules\Common\Helpers;

use Illuminate\Support\Carbon;

class ArrayValueGetter
{
    /**
     * @param array<string, mixed> $data
     */
    public static function int(array $data, string $key): int
    {
        return (int) $data[$key];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function intNull(array $data, string $key): ?int
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if (is_null($value)) {
            return null;
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function float(array $data, string $key): float
    {
        return (float) $data[$key];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function floatNull(array $data, string $key): ?float
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if (is_null($value)) {
            return null;
        }

        return (float) $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function intFloat(array $data, string $key): int|float
    {
        $value = $data[$key];

        return is_int($value) ? $value : ((float) $value);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function string(array $data, string $key): string
    {
        return (string) $data[$key];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function stringNull(array $data, string $key): ?string
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if (is_null($value)) {
            return null;
        }

        return (string) $value;
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function bool(array $data, string $key): bool
    {
        return filter_var($data[$key], FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function boolNull(array $data, string $key): ?bool
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if (is_null($value)) {
            return null;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function date(array $data, string $key): Carbon
    {
        return Carbon::parse($data[$key]);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return int[]
     */
    public static function arrayInt(array $data, string $key): array
    {
        return array_map('intval', $data[$key]);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return int[]|null
     */
    public static function arrayIntNull(array $data, string $key): ?array
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if (is_null($value)) {
            return null;
        }

        return array_map('intval', $value);
    }

    /**
     * @param array<string, mixed> $data
     *
     * @return string[]|null
     */
    public static function arrayStringNull(array $data, string $key): ?array
    {
        if (!array_key_exists($key, $data)) {
            return null;
        }

        $value = $data[$key];

        if (is_null($value)) {
            return null;
        }

        return array_map('strval', $value);
    }
}
