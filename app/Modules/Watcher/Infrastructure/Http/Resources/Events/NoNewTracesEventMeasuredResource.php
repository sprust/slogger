<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\NoNewTracesEventMeasuredObject;

/** What the checker saw. */
class NoNewTracesEventMeasuredResource extends AbstractApiResource
{
    private string $window_from;
    private string $window_to;

    public function __construct(NoNewTracesEventMeasuredObject $resource)
    {
        parent::__construct($resource);

        $this->window_from = $resource->windowFrom;
        $this->window_to   = $resource->windowTo;
    }
}
