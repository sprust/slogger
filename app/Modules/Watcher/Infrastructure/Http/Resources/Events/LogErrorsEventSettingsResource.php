<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\LogErrorsEventSettingsObject;

class LogErrorsEventSettingsResource extends AbstractApiResource
{
    private int $threshold;

    public function __construct(LogErrorsEventSettingsObject $resource)
    {
        parent::__construct($resource);

        $this->threshold = $resource->threshold;
    }
}
