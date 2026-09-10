<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\NoNewTracesEventSettingsObject;

/** What the watcher was set to when it went off. */
class NoNewTracesEventSettingsResource extends AbstractApiResource
{
    private int $period_minutes;

    public function __construct(NoNewTracesEventSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->period_minutes = $resource->periodMinutes;
    }
}
