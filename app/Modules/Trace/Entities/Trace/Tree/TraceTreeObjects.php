<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

readonly class TraceTreeObjects
{
    /**
     * @param TraceTreeObject[]           $items
     * @param TraceTreeServiceObject[]    $services
     * @param TraceTreeStringableObject[] $types
     * @param TraceTreeStringableObject[] $statuses
     */
    public function __construct(
        public int $count,
        public array $items,
        public array $services,
        public array $types,
        public array $statuses,
    ) {
    }
}
