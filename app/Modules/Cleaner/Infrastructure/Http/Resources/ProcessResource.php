<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Infrastructure\Http\Resources;

use App\Modules\Cleaner\Entities\ProcessObject;
use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;

class ProcessResource extends AbstractApiResource
{
    private string $id;
    private int $cleared_collections_count;
    private int $cleared_traces_count;
    private ?string $error;
    private ?string $cleared_at;
    private string $created_at;
    private string $updated_at;

    public function __construct(ProcessObject $resource)
    {
        parent::__construct($resource);

        $this->id                        = $resource->id;
        $this->cleared_collections_count = $resource->clearedCollectionsCount;
        $this->cleared_traces_count      = $resource->clearedTracesCount;
        $this->error                     = $resource->error;
        $this->cleared_at                = $resource->clearedAt?->toDateTimeString();
        $this->created_at                = $resource->createdAt->toDateTimeString();
        $this->updated_at                = $resource->updatedAt->toDateTimeString();
    }
}
