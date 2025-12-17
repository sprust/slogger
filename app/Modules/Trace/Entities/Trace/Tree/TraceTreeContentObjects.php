<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

readonly class TraceTreeContentObjects
{
    /**
     * @param TraceTreeServiceObject[]    $services
     * @param TraceTreeStringableObject[] $tags
     * @param TraceTreeStringableObject[] $types
     * @param TraceTreeStringableObject[] $statuses
     */
    public function __construct(
        public int $count,
        public array $services,
        public array $types,
        public array $tags,
        public array $statuses,
    ) {
    }
}
