<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace;

readonly class DeletedTracesObject
{
    public function __construct(
        public int $collectionsCount,
        public int $tracesCount,
    ) {
    }
}
