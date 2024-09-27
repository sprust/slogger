<?php

namespace App\Modules\Cleaner\Framework\Http\Resources;

use App\Modules\Cleaner\Domain\Entities\Objects\ProcessObject;
use App\Modules\Common\Framework\Http\Resources\AbstractApiResource;

class ProcessResource extends AbstractApiResource
{
    private string $id;
    private int $setting_id;
    private int $cleared_count;
    private ?string $error;
    private ?string $cleared_at;
    private string $created_at;
    private string $updated_at;

    public function __construct(ProcessObject $resource)
    {
        parent::__construct($resource);

        $this->id            = $resource->id;
        $this->setting_id    = $resource->settingId;
        $this->cleared_count = $resource->clearedCount;
        $this->error         = $resource->error;
        $this->cleared_at    = $resource->clearedAt?->toDateTimeString();
        $this->created_at    = $resource->createdAt->toDateTimeString();
        $this->updated_at    = $resource->updatedAt->toDateTimeString();
    }
}
