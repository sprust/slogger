<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Entry;

readonly class LogMergeHeadObject
{
    public function __construct(
        public int $slotIndex,
        public int $time,
        public bool $isPending
    ) {
    }
}
