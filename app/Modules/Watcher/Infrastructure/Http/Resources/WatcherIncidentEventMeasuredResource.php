<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Infrastructure\Http\Resources;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;

/**
 * What the checker saw. `growth_percent` appears here and among the settings: one is the
 * growth that was reached, the other the growth that was asked for.
 */
class WatcherIncidentEventMeasuredResource extends AbstractApiResource
{
    private ?int $buffer_count;
    private ?int $invalid_count;
    private ?string $since;
    private ?string $window_from;
    private ?string $window_to;
    private ?int $window_count;
    private ?float $window_per_minute;
    private ?float $baseline_per_minute;
    private ?float $growth_percent;
    private ?float $slowest;

    /**
     * @param array<string, mixed> $resource
     */
    public function __construct(array $resource)
    {
        parent::__construct($resource);

        $this->buffer_count        = ArrayValueGetter::intNull($resource, 'buffer_count');
        $this->invalid_count       = ArrayValueGetter::intNull($resource, 'invalid_count');
        $this->since               = ArrayValueGetter::stringNull($resource, 'since');
        $this->window_from         = ArrayValueGetter::stringNull($resource, 'window_from');
        $this->window_to           = ArrayValueGetter::stringNull($resource, 'window_to');
        $this->window_count        = ArrayValueGetter::intNull($resource, 'window_count');
        $this->window_per_minute   = ArrayValueGetter::floatNull($resource, 'window_per_minute');
        $this->baseline_per_minute = ArrayValueGetter::floatNull($resource, 'baseline_per_minute');
        $this->growth_percent      = ArrayValueGetter::floatNull($resource, 'growth_percent');
        $this->slowest             = ArrayValueGetter::floatNull($resource, 'slowest');
    }
}
