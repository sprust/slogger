<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventMeasuredObject;

/** What the checker saw. */
class BufferOverflowEventMeasuredResource extends AbstractApiResource
{
    private int $buffer_count;

    public function __construct(BufferOverflowEventMeasuredObject $resource)
    {
        parent::__construct($resource);

        $this->buffer_count = $resource->bufferCount;
    }
}
