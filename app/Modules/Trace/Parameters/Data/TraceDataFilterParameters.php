<?php

namespace App\Modules\Trace\Parameters\Data;

readonly class TraceDataFilterParameters
{
    /**
     * @param TraceDataFilterItemParameters[] $filter
     * @param string[]                        $fields
     */
    public function __construct(
        public array $filter = [],
        public array $fields = [],
    ) {
    }
}
