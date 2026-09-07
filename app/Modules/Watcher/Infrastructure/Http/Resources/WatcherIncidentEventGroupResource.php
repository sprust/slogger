<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
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

    /**
     * @param array<string, mixed> $resource
     */
    public function __construct(array $resource)
    {
        parent::__construct($resource);

        $this->service_id   = ArrayValueGetter::intNull($resource, 'service_id') ?? 0;
        $this->type         = ArrayValueGetter::stringNull($resource, 'type') ?? '';
        $this->tags         = array_values(ArrayValueGetter::arrayStringNull($resource, 'tags') ?? []);
        $this->count        = ArrayValueGetter::intNull($resource, 'count') ?? 0;
        $this->duration_max = ArrayValueGetter::floatNull($resource, 'duration_max');
        $this->trace_id     = ArrayValueGetter::stringNull($resource, 'trace_id');
    }
}
