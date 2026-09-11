<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Watcher\Entities\WatcherIncidentEventGroupObject;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

/**
 * One shape of trace behind an event: what it was, how many, and — where the watcher was
 * about duration — the slowest of them by name.
 */
class WatcherIncidentEventGroupResource extends AbstractApiResource
{
    private int $service_id;
    private string $type;
    /** @var string[] */
    #[OaListItemTypeAttribute('string')]
    private array $tags;
    private int $count;
    private ?float $duration_max;
    private ?string $trace_id;
    private ?string $trace_logged_at;

    public function __construct(WatcherIncidentEventGroupObject $resource)
    {
        parent::__construct($resource);

        $this->service_id      = $resource->serviceId;
        $this->type            = $resource->type;
        $this->tags            = $resource->tags;
        $this->count           = $resource->count;
        $this->duration_max    = $resource->durationMax;
        $this->trace_id        = $resource->slowestTraceId;
        $this->trace_logged_at = $resource->slowestTraceLoggedAt;
    }
}
