<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\LogErrorsEventPayloadObject;

class LogErrorsEventPayloadResource extends AbstractApiResource
{
    private LogErrorsEventSettingsResource $settings;
    private LogErrorsEventMeasuredResource $measured;

    public function __construct(LogErrorsEventPayloadObject $resource)
    {
        parent::__construct($resource);

        $this->settings = new LogErrorsEventSettingsResource($resource->settings);
        $this->measured = new LogErrorsEventMeasuredResource($resource->measured);
    }
}
