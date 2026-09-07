<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\WatcherTypeFieldObject;

class WatcherTypeFieldResource extends AbstractApiResource
{
    private string $key;
    private string $title;
    private string $value_type;
    /** Always a number; `value_type` says whether the form should keep it whole. */
    private float $default;

    public function __construct(WatcherTypeFieldObject $resource)
    {
        parent::__construct($resource);

        $this->key        = $resource->key;
        $this->title      = $resource->title;
        $this->value_type = $resource->valueType;
        $this->default    = (float) $resource->default;
    }
}
