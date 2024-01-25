<?php

namespace App\Modules\Traces\Repository;

use App\Models\Traces\Trace;
use App\Modules\Traces\Dto\Parameters\TraceCreateParameters;
use App\Modules\Traces\Dto\Parameters\TraceCreateParametersList;
use MongoDB\BSON\UTCDateTime;

class TracesRepository implements TracesRepositoryInterface
{
    public function createMany(TraceCreateParametersList $parametersList): void
    {
        $createdAt = new UTCDateTime(now());

        $documents = array_map(
            fn(TraceCreateParameters $parameters) => [
                'serviceId'     => $parameters->serviceId,
                'traceId'       => $parameters->traceId,
                'parentTraceId' => $parameters->parentTraceId,
                'type'          => $parameters->type->value,
                'tags'          => $parameters->tags,
                'data'          => json_decode($parameters->data, true),
                'loggedAt'      => new UTCDateTime($parameters->loggedAt),
                'createdAt'     => $createdAt,
                'updatedAt'     => $createdAt,
            ],
            $parametersList->getItems()
        );

        Trace::collection()->insertMany($documents);
    }
}
