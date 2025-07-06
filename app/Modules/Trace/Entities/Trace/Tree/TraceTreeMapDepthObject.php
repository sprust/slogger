<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

class TraceTreeMapDepthObject
{
    public function __construct(
        public int $order,
        public int $depth,
    ) {
    }
}
