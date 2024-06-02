<?php

namespace App\Modules\TraceCleaner\Framework\Http\Resources;

use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;
use App\Modules\TraceCleaner\Domain\Entities\Objects\ProcessObject;

class ProcessResource extends AbstractApiResource
{
    private int $id;
    private int $setting_id;
    private int $cleared_count;
    private ?string $cleared_at;
    private string $created_at;
    private string $updated_at;

    public function __construct(ProcessObject $resource)
    {
        parent::__construct($resource);

        $this->id            = $resource->id;
        $this->setting_id    = $resource->settingId;
        $this->cleared_count = $resource->clearedCount;
        $this->cleared_at    = $resource->clearedAt?->toDateTimeString();
        $this->created_at    = $resource->createdAt->toDateTimeString();
        $this->updated_at    = $resource->updatedAt->toDateTimeString();
    }
}
