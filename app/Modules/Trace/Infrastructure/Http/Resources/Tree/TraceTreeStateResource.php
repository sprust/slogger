<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Resources\Tree;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;

class TraceTreeStateResource extends AbstractApiResource
{
    private string $root_trace_id;
    private string $version;
    private string $status;
    private int $count;
    private ?string $error;
    private ?string $started_at;
    private ?string $finished_at;
    private string $created_at;
    private string $updated_at;

    public function __construct(TraceTreeCacheStateObject $resource)
    {
        parent::__construct($resource);

        $this->root_trace_id = $resource->rootTraceId;
        $this->version       = $resource->version;
        $this->status        = $resource->status->value;
        $this->count         = $resource->count;
        $this->error         = $resource->error;
        $this->started_at    = $resource->startedAt?->toIso8601String();
        $this->finished_at   = $resource->finishedAt?->toIso8601String();
        $this->created_at    = $resource->createdAt->toIso8601String();
        $this->updated_at    = $resource->updatedAt->toIso8601String();
    }
}
