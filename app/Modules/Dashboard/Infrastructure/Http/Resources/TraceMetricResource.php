<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Entities\TraceMetricObject;

class TraceMetricResource extends AbstractApiResource
{
    private string $type;
    private string $timestamp;
    private int $logged;
    private int $buffered;
    private int $stored;

    public function __construct(TraceMetricObject $resource)
    {
        parent::__construct($resource);

        $this->type      = $resource->type;
        $this->timestamp = $resource->timestamp->toDateTimeString();
        $this->logged    = $resource->logged;
        $this->buffered  = $resource->buffered;
        $this->stored    = $resource->stored;
    }
}
