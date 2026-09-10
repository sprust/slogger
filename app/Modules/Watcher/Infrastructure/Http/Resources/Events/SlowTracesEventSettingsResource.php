<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\SlowTracesEventSettingsObject;

/** What the watcher was set to when it went off. */
class SlowTracesEventSettingsResource extends AbstractApiResource
{
    private float $duration;
    private int $window_minutes;

    public function __construct(SlowTracesEventSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->duration       = $resource->duration;
        $this->window_minutes = $resource->windowMinutes;
    }
}
