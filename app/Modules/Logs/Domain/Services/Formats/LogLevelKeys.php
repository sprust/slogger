<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Formats;

use App\Modules\Logs\Enums\LogTypeEnum;

readonly class LogLevelKeys
{
    public const string NONE = 'none';

    public function __construct(
        private LogFormatRegistry $logFormatRegistry
    ) {
    }

    public function makeKey(LogTypeEnum $type, int $level): string
    {
        foreach ($this->logFormatRegistry->get($type)->getLevelNames() as $levelName) {
            if ($levelName->level === $level) {
                return sprintf('%s.%s', $type->value, $levelName->name);
            }
        }

        return sprintf('%s.%s', $type->value, self::NONE);
    }

    /**
     * @param list<string> $keys
     *
     * @return list<int>
     */
    public function parseKeys(array $keys, LogTypeEnum $type): array
    {
        $prefix = $type->value . '.';

        $levels = [];

        foreach ($keys as $key) {
            if (!str_starts_with($key, $prefix)) {
                continue;
            }

            $name = substr($key, strlen($prefix));

            if ($name === self::NONE) {
                $levels[] = 0;

                continue;
            }

            foreach ($this->logFormatRegistry->get($type)->getLevelNames() as $levelName) {
                if ($levelName->name === $name) {
                    $levels[] = $levelName->level;
                }
            }
        }

        return $levels;
    }
}
