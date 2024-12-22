<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Timestamp;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampObject;

class TraceTimestampPeriodTimestampResource extends AbstractApiResource
{
    private string $value;
    private string $title;

    public function __construct(TraceTimestampObject $resource)
    {
        parent::__construct($resource);

        $this->value = $resource->value;
        $this->title = $resource->title;
    }
}
