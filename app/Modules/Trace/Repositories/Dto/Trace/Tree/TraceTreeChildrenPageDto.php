<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\Trace\Tree;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeChildrenCursorObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawObject;

readonly class TraceTreeChildrenPageDto
{
    /**
     * @param TraceTreeRawObject[] $items
     */
    public function __construct(
        public array $items,
        public ?TraceTreeChildrenCursorObject $next,
    ) {
    }
}
