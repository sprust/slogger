<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Dashboard\Entities\SconcurRequestsObject;

class SconcurRequestsResource extends AbstractApiResource
{
    private int $completed;
    private float $avg_ms;
    private int $in_flight;
    private int $in_flight_1_to_5s;
    private int $in_flight_5_to_15s;
    private int $in_flight_over_15s;

    public function __construct(SconcurRequestsObject $requests)
    {
        parent::__construct($requests);

        $this->completed          = $requests->completed;
        $this->avg_ms             = $requests->avgMs;
        $this->in_flight          = $requests->inFlight;
        $this->in_flight_1_to_5s  = $requests->inFlight1to5s;
        $this->in_flight_5_to_15s = $requests->inFlight5to15s;
        $this->in_flight_over_15s = $requests->inFlightOver15s;
    }
}
