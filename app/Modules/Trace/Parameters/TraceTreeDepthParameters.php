<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters;

class TraceTreeDepthParameters
{
    public function __construct(
        public int $order,
        public int $depth,
    ) {
    }
}
