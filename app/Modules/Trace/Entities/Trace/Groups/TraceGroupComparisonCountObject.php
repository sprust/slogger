<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Groups;

readonly class TraceGroupComparisonCountObject
{
    public function __construct(
        public string|int|float|bool|null $value,
        public bool $inGroupA,
        public int $count
    ) {
    }
}
