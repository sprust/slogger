<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\ManyTracesEventSettingsObject;

/** What the watcher was set to when it went off. */
class ManyTracesEventSettingsResource extends AbstractApiResource
{
    private int $window_minutes;
    private int $threshold;

    public function __construct(ManyTracesEventSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->window_minutes = $resource->windowMinutes;
        $this->threshold      = $resource->threshold;
    }
}
