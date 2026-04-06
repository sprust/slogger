<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Entities\DatabaseStatCacheObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class DatabaseStatListResource extends AbstractApiResource
{
    private string $cached_at;
    #[OaListItemTypeAttribute(DatabaseResource::class)]
    private array $items;

    public function __construct(DatabaseStatCacheObject $resource)
    {
        parent::__construct($resource);

        $this->cached_at = $resource->cachedAt;
        $this->items     = DatabaseResource::mapIntoMe($resource->stats);
    }
}
