<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Settings\BufferOverflowSettingsObject;

/**
 * The settings of one watcher, typed because the endpoint that answers with them is the
 * one belonging to this type.
 */
class BufferOverflowWatcherSettingsResource extends AbstractApiResource
{
    private int $id;
    private int $threshold;

    public function __construct(int $id, BufferOverflowSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->id        = $id;
        $this->threshold = $resource->threshold;
    }
}
