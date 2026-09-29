<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

readonly class TraceTreeStateObject
{
    public function __construct(
        public string $rootTraceId,
        public ?TraceTreeCacheStateObject $state
    ) {
    }
}
