<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Logs\Entities\Log\LogObject;

class LogResource extends AbstractApiResource
{
    private string $level;
    private string $message;
    private string $context;
    private string $channel;
    private string $logged_at;

    public function __construct(LogObject $resource)
    {
        parent::__construct($resource);

        $this->level     = $resource->level;
        $this->message   = $resource->message;
        $this->context   = json_encode($resource->context);
        $this->channel   = $resource->channel;
        $this->logged_at = $resource->loggedAt->toDateTimeString();
    }
}
