<?php

namespace App\Modules\TraceCleaner\Framework\Http\Resources;

use App\Modules\Common\Http\Resources\AbstractApiResource;
use App\Modules\TraceCleaner\Domain\Entities\Objects\SettingObject;

class SettingResource extends AbstractApiResource
{
    private int $id;
    private int $days_lifetime;
    private ?string $type;
    private bool $deleted;
    private string $created_at;
    private string $updated_at;

    public function __construct(SettingObject $resource)
    {
        parent::__construct($resource);

        $this->id            = $resource->id;
        $this->days_lifetime = $resource->daysLifetime;
        $this->deleted       = $resource->deleted;
        $this->type          = $resource->type;
        $this->created_at    = $resource->createdAt->toDateTimeString();
        $this->updated_at    = $resource->updatedAt->toDateTimeString();
    }
}
