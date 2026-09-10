<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\SlowTracesEventMeasuredObject;

/** What the checker saw. */
class SlowTracesEventMeasuredResource extends AbstractApiResource
{
    private float $slowest;

    public function __construct(SlowTracesEventMeasuredObject $resource)
    {
        parent::__construct($resource);

        $this->slowest = $resource->slowest;
    }
}
