<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\DynamicIndex;

readonly class TraceDynamicIndexFieldObject
{
    public function __construct(
        public string $name,
        public string $title
    ) {
    }
}
