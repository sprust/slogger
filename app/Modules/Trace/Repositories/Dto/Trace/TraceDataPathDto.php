<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace;

/**
 * A path of the trace data as the SQL reaches it.
 */
readonly class TraceDataPathDto
{
    /**
     * @param string[] $segments      the parts of the path, each one checked to be a plain name
     * @param string[] $arraySegments the first parts of the path that hold an array of objects, empty if none
     */
    public function __construct(
        public array $segments,
        // `dt.a.b`: the value when the path runs through objects only
        public string $objectExpression,
        // `dt.a[].b`: the values when a part of the path is an array of objects, else null
        public ?string $arrayExpression,
        public array $arraySegments = [],
    ) {
    }
}
