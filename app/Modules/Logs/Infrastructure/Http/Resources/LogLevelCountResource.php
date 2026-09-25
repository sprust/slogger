<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Logs\Entities\Entry\LogLevelKeyCountObject;

class LogLevelCountResource extends AbstractApiResource
{
    private string $key;
    private int $count;

    public function __construct(LogLevelKeyCountObject $resource)
    {
        parent::__construct($resource);

        $this->key   = $resource->key;
        $this->count = $resource->count;
    }
}
