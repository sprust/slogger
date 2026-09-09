<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Settings\InvalidBufferGrownSettingsObject;

class InvalidBufferGrownWatcherSettingsResource extends AbstractApiResource
{
    private int $id;
    private int $threshold;

    public function __construct(int $id, InvalidBufferGrownSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->id        = $id;
        $this->threshold = $resource->threshold;
    }
}
