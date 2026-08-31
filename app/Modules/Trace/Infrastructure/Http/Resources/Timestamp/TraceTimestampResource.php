<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Timestamp;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Timestamp\TraceTimestampsObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class TraceTimestampResource extends AbstractApiResource
{
    private string $timestamp;
    private string $timestamp_to;
    #[OaListItemTypeAttribute(TraceTimestampFieldResource::class)]
    private array $fields;

    public function __construct(TraceTimestampsObject $resource)
    {
        parent::__construct($resource);

        $this->timestamp = $resource->timestamp->toDateTimeString();
        // With microseconds, and not for decoration: the end of a bucket is a microsecond
        // before the next one starts, so cutting it back to whole seconds would hand the
        // search a bound that drops everything logged inside the bucket's last second.
        $this->timestamp_to = $resource->timestampTo->format('Y-m-d H:i:s.u');
        $this->fields       = TraceTimestampFieldResource::mapIntoMe($resource->fields);
    }
}
