<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Entities\SconcurConsumersObject;

class SconcurConsumersResource extends AbstractApiResource
{
    private int $coroutines;
    private int $delivered;
    private int $acked;
    private int $refused;
    private int $timed;
    private float $avg_ms;
    private int $in_flight;
    private int $in_flight_1_to_5s;
    private int $in_flight_5_to_15s;
    private int $in_flight_over_15s;

    public function __construct(SconcurConsumersObject $consumers)
    {
        parent::__construct($consumers);

        $this->coroutines         = $consumers->coroutines;
        $this->delivered          = $consumers->delivered;
        $this->acked              = $consumers->acked;
        $this->refused            = $consumers->refused;
        $this->timed              = $consumers->timed;
        $this->avg_ms             = $consumers->avgMs;
        $this->in_flight          = $consumers->inFlight;
        $this->in_flight_1_to_5s  = $consumers->inFlight1to5s;
        $this->in_flight_5_to_15s = $consumers->inFlight5to15s;
        $this->in_flight_over_15s = $consumers->inFlightOver15s;
    }
}
