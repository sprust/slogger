<?php

declare(strict_types=1);

namespace App\Modules\Trace\Parameters;

/**
 * What a node of a tree must match to be shown; an empty list does not narrow anything.
 */
readonly class TraceTreeFilterParameters
{
    /**
     * @param int[]    $serviceIds
     * @param string[] $types
     * @param string[] $tags       any one of them is enough
     * @param string[] $statuses
     */
    public function __construct(
        public array $serviceIds,
        public array $types,
        public array $tags,
        public array $statuses,
    ) {
    }
}
