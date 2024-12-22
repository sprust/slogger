<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\DynamicIndex;

readonly class TraceDynamicIndexFieldDto
{
    public function __construct(
        public string $fieldName
    ) {
    }
}
