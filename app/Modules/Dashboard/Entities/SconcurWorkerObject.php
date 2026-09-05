<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Entities;

readonly class SconcurWorkerObject
{
    public function __construct(
        public int $pid,
        public string $group,
        public bool $hung,
        public float $uptimeSeconds,
        public float $cpuPercent,
        public int $memoryRssBytes,
        public int $runtimeTasks,
        public ?SconcurWorkObject $work = null,
    ) {
    }
}
