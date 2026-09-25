<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Logs\Entities\Entry\LogEntryFieldObject;

class LogEntryFieldResource extends AbstractApiResource
{
    private string $key;
    private ?string $value;

    public function __construct(LogEntryFieldObject $resource)
    {
        parent::__construct($resource);

        $this->key   = $resource->key;
        $this->value = $resource->value;
    }
}
