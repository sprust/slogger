<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace\Tree;

readonly class TraceTreeCachePageDto
{
    /**
     * @param string[] $traceIds
     */
    public function __construct(
        public array $traceIds,
        public ?string $lastId,
    ) {
    }
}
