<?php

namespace App\Modules\Trace\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\Trace\Domain\Entities\Objects\TraceAdminStoreObject;

class TraceAdminStoreResource extends AbstractApiResource
{
    private string $id;
    private string $title;
    private int $store_version;
    private string $store_data;
    private ?string $used_at;
    private string $created_at;

    public function __construct(TraceAdminStoreObject $resource)
    {
        parent::__construct($resource);

        $this->id            = $resource->id;
        $this->title         = $resource->title;
        $this->store_version = $resource->storeVersion;
        $this->store_data    = $resource->storeData;
        $this->used_at       = $resource->usedAt?->toDateTimeString();
        $this->created_at    = $resource->createdAt->toDateTimeString();
    }
}
