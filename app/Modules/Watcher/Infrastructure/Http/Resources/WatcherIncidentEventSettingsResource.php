<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;

/**
 * What the watcher was set to when it went off, keyed the way its settings object keys it.
 *
 * Every type's fields in one shape, the ones that do not apply simply absent: an event is
 * read through its incident, and that route names no type.
 */
class WatcherIncidentEventSettingsResource extends AbstractApiResource
{
    private ?int $threshold;
    private ?int $period_minutes;
    private ?int $window_minutes;
    private ?int $baseline_minutes;
    private ?float $growth_percent;
    private ?float $duration;

    /**
     * @param array<string, mixed> $resource
     */
    public function __construct(array $resource)
    {
        parent::__construct($resource);

        $this->threshold        = ArrayValueGetter::intNull($resource, 'threshold');
        $this->period_minutes   = ArrayValueGetter::intNull($resource, 'period_minutes');
        $this->window_minutes   = ArrayValueGetter::intNull($resource, 'window_minutes');
        $this->baseline_minutes = ArrayValueGetter::intNull($resource, 'baseline_minutes');
        $this->growth_percent   = ArrayValueGetter::floatNull($resource, 'growth_percent');
        $this->duration         = ArrayValueGetter::floatNull($resource, 'duration');
    }
}
