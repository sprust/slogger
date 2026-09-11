<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Repositories;

use App\Models\Traces\TraceMetric;
use App\Modules\Dashboard\Entities\TraceMetricObject;
use App\Modules\Dashboard\Repositories\Services\TraceMetricReader;
use Illuminate\Support\Carbon;
use SConcur\Bson\UTCDateTime;

readonly class TraceMetricRepository
{
    public function __construct(
        private TraceMetricReader $reader
    ) {
    }

    /**
     * @return TraceMetricObject[]
     */
    public function find(int $serviceId, Carbon $from): array
    {
        $documents = [];

        foreach (
            TraceMetric::sconcur()->find(
                filter: [
                    'sid' => $serviceId,
                    't'   => ['$gte' => new UTCDateTime($from)],
                ],
                projection: ['_id' => 0, 'tp' => 1, 't' => 1, 'lc' => 1, 'bc' => 1, 'sc' => 1],
            ) as $document
        ) {
            $documents[] = $document;
        }

        return $this->reader->read($documents);
    }
}
