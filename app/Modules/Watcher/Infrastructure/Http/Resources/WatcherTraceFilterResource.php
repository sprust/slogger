<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\Settings\WatcherTraceFilterObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class WatcherTraceFilterResource extends AbstractApiResource
{
    /** @var int[] */
    #[OaListItemTypeAttribute('int')]
    private array $service_ids;
    /** @var string[] */
    #[OaListItemTypeAttribute('string')]
    private array $types;
    /** @var string[] */
    #[OaListItemTypeAttribute('string')]
    private array $tags;

    public function __construct(WatcherTraceFilterObject $resource)
    {
        parent::__construct($resource);

        $this->service_ids = $resource->serviceIds;
        $this->types       = $resource->types;
        $this->tags        = $resource->tags;
    }
}
