<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Index;

use App\Modules\Logs\Entities\Index\LogLevelCountObject;

class LogLevelCounter
{
    /**
     * @var int[]
     */
    private array $counts = [];

    /**
     * @param list<LogLevelCountObject> $levelCounts
     */
    public function __construct(array $levelCounts = [])
    {
        foreach ($levelCounts as $levelCount) {
            $this->add($levelCount->level, $levelCount->count);
        }
    }

    public function add(int $level, int $count): void
    {
        $this->counts[$level] = ($this->counts[$level] ?? 0) + $count;

        if ($this->counts[$level] <= 0) {
            unset($this->counts[$level]);
        }
    }

    public function get(int $level): int
    {
        return $this->counts[$level] ?? 0;
    }

    /**
     * @return list<LogLevelCountObject>
     */
    public function getLevelCounts(): array
    {
        ksort($this->counts);

        $levelCounts = [];

        foreach ($this->counts as $level => $count) {
            $levelCounts[] = new LogLevelCountObject(level: $level, count: $count);
        }

        return $levelCounts;
    }
}
