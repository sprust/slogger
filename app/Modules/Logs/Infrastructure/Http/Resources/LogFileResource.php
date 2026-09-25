<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Logs\Entities\File\LogFileObject;
use Illuminate\Support\Carbon;

class LogFileResource extends AbstractApiResource
{
    private string $id;
    private string $name;
    private string $source;
    private string $folder;
    private string $type;
    private int $size_bytes;
    private bool $can_delete;
    private string $modified_at;

    public function __construct(LogFileObject $resource)
    {
        parent::__construct($resource);

        $this->id          = $resource->id;
        $this->name        = $resource->name;
        $this->source      = $resource->source;
        $this->folder      = $resource->folder;
        $this->type        = $resource->type->value;
        $this->size_bytes  = $resource->sizeBytes;
        $this->can_delete  = $resource->deletable;
        $this->modified_at = Carbon::createFromTimestampMs($resource->modifiedAtMs)->toDateTimeString();
    }
}
