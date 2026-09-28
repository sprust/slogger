<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Groups;

readonly class TraceGroupComparisonRowObject
{
    public function __construct(
        public string|int|float|bool|null $value,
        public int $groupACount,
        public float $groupAShare,
        public int $groupBCount,
        public float $groupBShare
    ) {
    }
}
