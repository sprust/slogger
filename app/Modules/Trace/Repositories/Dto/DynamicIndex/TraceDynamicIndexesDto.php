<?php

namespace App\Modules\Trace\Repositories\Dto\DynamicIndex;

use Illuminate\Support\Carbon;

readonly class TraceDynamicIndexesDto
{
    /**
     * @param TraceDynamicIndexFieldDto[] $fieldNames
     */
    public function __construct(
        public array $fieldNames,
        public ?Carbon $loggedAtFrom = null,
        public ?Carbon $loggedAtTo = null,
    ) {
    }
}
