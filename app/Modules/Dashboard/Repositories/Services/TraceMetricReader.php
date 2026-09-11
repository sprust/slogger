<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Repositories\Services;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Dashboard\Entities\TraceMetricObject;
use Illuminate\Support\Carbon;
use SConcur\Bson\UTCDateTime;

readonly class TraceMetricReader
{
    /**
     * @param array<int, mixed> $documents
     *
     * @return TraceMetricObject[]
     */
    public function read(array $documents): array
    {
        $metrics = [];

        foreach ($documents as $document) {
            if (!is_array($document)) {
                continue;
            }

            /** @var array<string, mixed> $document */
            $type = ArrayValueGetter::stringNull($document, 'tp');
            $slot = $document['t'] ?? null;

            if (is_null($type) || !$slot instanceof UTCDateTime) {
                continue;
            }

            $metrics[] = new TraceMetricObject(
                type: $type,
                timestamp: new Carbon($slot->toDateTime()),
                logged: ArrayValueGetter::intNull($document, 'lc') ?? 0,
                buffered: ArrayValueGetter::intNull($document, 'bc') ?? 0,
                stored: ArrayValueGetter::intNull($document, 'sc') ?? 0
            );
        }

        usort(
            $metrics,
            static fn(TraceMetricObject $a, TraceMetricObject $b): int => [$a->timestamp->getTimestamp(), $a->type]
                <=> [$b->timestamp->getTimestamp(), $b->type]
        );

        return $metrics;
    }
}
