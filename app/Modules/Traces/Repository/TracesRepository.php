<?php

namespace App\Modules\Traces\Repository;

use App\Models\Traces\Trace;
use App\Modules\Traces\Repository\Exceptions\TraceAlreadyExistsException;
use App\Modules\Traces\Repository\Parameters\TraceCreateParameters;
use App\Modules\Traces\Repository\Parameters\TraceCreateParametersList;
use Illuminate\Database\Eloquent\Builder;
use MongoDB\BSON\UTCDateTime;

class TracesRepository
{
    /**
     * @throws TraceAlreadyExistsException
     */
    public function create(TraceCreateParameters $parameters): void
    {
        $this->createMany(
            (new TraceCreateParametersList())->add($parameters)
        );
    }

    /**
     * @throws TraceAlreadyExistsException
     */
    public function createMany(TraceCreateParametersList $parametersList): void
    {
        $parametersListItems = $parametersList->getItems();

        $traceIds = array_map(
            fn(TraceCreateParameters $parameters) => $parameters->traceId,
            $parametersListItems
        );

        $existTraceIds = $this->makeBuilder(traceIds: $traceIds)
            ->pluck('traceId')
            ->toArray();

        if ($existTraceIds) {
            throw new TraceAlreadyExistsException($existTraceIds);
        }

        $createdAt = new UTCDateTime(now());

        $documents = array_map(
            fn(TraceCreateParameters $parameters) => [
                'service'       => $parameters->service,
                'traceId'       => $parameters->traceId,
                'parentTraceId' => $parameters->parentTraceId,
                'type'          => $parameters->type->value,
                'tags'          => $parameters->tags,
                'data'          => $parameters->data,
                'loggedAt'      => new UTCDateTime($parameters->loggedAt),
                'createdAt'     => $createdAt,
                'updatedAt'     => $createdAt,
            ],
            $parametersListItems
        );

        Trace::collection()->insertMany($documents);
    }

    /**
     * @param array $traceIds
     *
     * @return Builder|Trace
     */
    private function makeBuilder(array $traceIds): Builder
    {
        return Trace::query()->whereIn('traceId', $traceIds);
    }
}
