<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

/**
 * What a watcher saw, in one shape covering every type.
 *
 * Not one shape per type, unlike the settings: an event is read through the incident it
 * belongs to, and that route does not name a type — putting one in it would mean five
 * more routes on a view that only reads. So the fields of every type live here and the
 * ones that do not apply come back null, which the panel already knows how to read: it
 * has the watcher's type beside the incident.
 *
 * Loose here and strict on the writing side is not an inconsistency. A request that
 * accepts a field it should not silently changes what a watcher measures; a response that
 * carries a null nobody reads changes nothing.
 */
class WatcherIncidentEventPayloadResource extends AbstractApiResource
{
    private ?int $threshold;
    private ?int $buffer_count;
    private ?int $invalid_count;
    private ?string $since;
    private ?int $period_minutes;
    private ?string $window_from;
    private ?string $window_to;
    private ?int $window_minutes;
    private ?int $window_count;
    private ?float $window_per_minute;
    private ?int $baseline_minutes;
    private ?float $baseline_per_minute;
    private ?float $growth_percent;
    private ?int $threshold_percent;
    private ?float $duration;
    private ?float $slowest;
    /** @var WatcherIncidentEventGroupResource[] */
    #[OaListItemTypeAttribute(WatcherIncidentEventGroupResource::class)]
    private array $groups;

    /**
     * @param array<string, mixed> $resource
     */
    public function __construct(array $resource)
    {
        parent::__construct($resource);

        $this->threshold           = ArrayValueGetter::intNull($resource, 'threshold');
        $this->buffer_count        = ArrayValueGetter::intNull($resource, 'buffer_count');
        $this->invalid_count       = ArrayValueGetter::intNull($resource, 'invalid_count');
        $this->since               = ArrayValueGetter::stringNull($resource, 'since');
        $this->period_minutes      = ArrayValueGetter::intNull($resource, 'period_minutes');
        $this->window_from         = ArrayValueGetter::stringNull($resource, 'window_from');
        $this->window_to           = ArrayValueGetter::stringNull($resource, 'window_to');
        $this->window_minutes      = ArrayValueGetter::intNull($resource, 'window_minutes');
        $this->window_count        = ArrayValueGetter::intNull($resource, 'window_count');
        $this->window_per_minute   = ArrayValueGetter::floatNull($resource, 'window_per_minute');
        $this->baseline_minutes    = ArrayValueGetter::intNull($resource, 'baseline_minutes');
        $this->baseline_per_minute = ArrayValueGetter::floatNull($resource, 'baseline_per_minute');
        $this->growth_percent      = ArrayValueGetter::floatNull($resource, 'growth_percent');
        $this->threshold_percent   = ArrayValueGetter::intNull($resource, 'threshold_percent');
        $this->duration            = ArrayValueGetter::floatNull($resource, 'duration');
        $this->slowest             = ArrayValueGetter::floatNull($resource, 'slowest');
        $this->groups              = $this->groupsOf($resource);
    }

    /**
     * @param array<string, mixed> $resource
     *
     * @return WatcherIncidentEventGroupResource[]
     */
    private function groupsOf(array $resource): array
    {
        $groups = $resource['groups'] ?? [];

        if (!is_array($groups)) {
            return [];
        }

        $resources = [];

        foreach ($groups as $group) {
            if (is_array($group)) {
                /** @var array<string, mixed> $group */
                $resources[] = new WatcherIncidentEventGroupResource($group);
            }
        }

        return $resources;
    }
}
