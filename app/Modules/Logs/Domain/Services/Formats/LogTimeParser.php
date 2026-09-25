<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Formats;

readonly class LogTimeParser
{
    private const array MONTHS = [
        'Jan' => 1,
        'Feb' => 2,
        'Mar' => 3,
        'Apr' => 4,
        'May' => 5,
        'Jun' => 6,
        'Jul' => 7,
        'Aug' => 8,
        'Sep' => 9,
        'Oct' => 10,
        'Nov' => 11,
        'Dec' => 12,
    ];

    public function toUnixTime(
        int $year,
        int $month,
        int $day,
        int $hour,
        int $minute,
        int $second,
        ?string $zone
    ): ?int {
        $time = gmmktime($hour, $minute, $second, $month, $day, $year);

        if ($time === false || $time < 0) {
            return null;
        }

        return $time - $this->parseZoneOffset($zone);
    }

    public function parseMonth(string $name): ?int
    {
        return self::MONTHS[ucfirst(strtolower($name))] ?? null;
    }

    private function parseZoneOffset(?string $zone): int
    {
        if ($zone === null || $zone === '' || $zone === 'Z') {
            return 0;
        }

        $digits = str_replace(':', '', substr($zone, 1));

        $offset = (int) substr($digits, 0, 2) * 3600 + (int) substr($digits, 2, 2) * 60;

        return $zone[0] === '-' ? -$offset : $offset;
    }
}
