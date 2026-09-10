<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\InvalidBufferGrownEventMeasuredObject;

/** What the checker saw. */
class InvalidBufferGrownEventMeasuredResource extends AbstractApiResource
{
    private int $invalid_count;
    private string $since;

    public function __construct(InvalidBufferGrownEventMeasuredObject $resource)
    {
        parent::__construct($resource);

        $this->invalid_count = $resource->invalidCount;
        $this->since         = $resource->since;
    }
}
