<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

readonly class TraceTreeContentResultObject
{
    public function __construct(
        public TraceTreeCacheStateObject $state,
        public ?TraceTreeContentObjects $content,
    ) {
    }
}
