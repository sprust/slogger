<?php

namespace App\Modules\Trace\Domain\Entities\Objects;

readonly class TraceDynamicIndexFieldObject
{
    public function __construct(
        public string $name,
        public string $title
    ) {
    }
}
