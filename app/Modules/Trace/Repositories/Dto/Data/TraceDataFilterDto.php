<?php

namespace App\Modules\Trace\Repositories\Dto\Data;

readonly class TraceDataFilterDto
{
    /**
     * @param TraceDataFilterItemDto[] $filter
     * @param string[]                 $fields
     */
    public function __construct(
        public array $filter = [],
        public array $fields = [],
    ) {
    }
}
