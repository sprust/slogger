<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources\Events;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Events\LogErrorsEventMeasuredObject;

class LogErrorsEventMeasuredResource extends AbstractApiResource
{
    private int $error_count;
    private string $since;
    private string $last_message;

    public function __construct(LogErrorsEventMeasuredObject $resource)
    {
        parent::__construct($resource);

        $this->error_count  = $resource->errorCount;
        $this->since        = $resource->since;
        $this->last_message = $resource->lastMessage;
    }
}
