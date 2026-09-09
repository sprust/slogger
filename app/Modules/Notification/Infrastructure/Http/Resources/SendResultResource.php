<?php

declare(strict_types=1);

namespace App\Modules\Notification\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Notification\Entities\SendResultObject;

class SendResultResource extends AbstractApiResource
{
    private bool $delivered;
    private bool $permanent;
    private ?string $error;
    private ?int $retry_after_seconds;

    public function __construct(SendResultObject $resource)
    {
        parent::__construct($resource);

        $this->delivered           = $resource->delivered;
        $this->permanent           = $resource->permanent;
        $this->error               = $resource->error;
        $this->retry_after_seconds = $resource->retryAfterSeconds;
    }
}
