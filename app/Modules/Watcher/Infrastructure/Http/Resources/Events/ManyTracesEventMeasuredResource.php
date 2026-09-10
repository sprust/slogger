<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\ManyTracesEventMeasuredObject;

/** What the checker saw. */
class ManyTracesEventMeasuredResource extends AbstractApiResource
{
    private int $window_count;

    public function __construct(ManyTracesEventMeasuredObject $resource)
    {
        parent::__construct($resource);

        $this->window_count = $resource->windowCount;
    }
}
