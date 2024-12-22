<?php

declare(strict_types=1);

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
