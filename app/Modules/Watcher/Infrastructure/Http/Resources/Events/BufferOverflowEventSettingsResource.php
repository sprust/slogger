<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\BufferOverflowEventSettingsObject;

/** What the watcher was set to when it went off. */
class BufferOverflowEventSettingsResource extends AbstractApiResource
{
    private int $threshold;

    public function __construct(BufferOverflowEventSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->threshold = $resource->threshold;
    }
}
