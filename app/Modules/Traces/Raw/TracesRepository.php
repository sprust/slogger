<?php

namespace App\Modules\Traces\Raw;

use App\Models\Traces\Trace;
use App\Modules\Traces\Raw\Exceptions\TraceAlreadyExistsException;
use App\Modules\Traces\Raw\Parameters\TraceCreateParameters;
use App\Modules\Traces\Raw\Parameters\TraceCreateParametersList;
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

        $trackIds = array_map(
            fn(TraceCreateParameters $parameters) => $parameters->trackId,
            $parametersListItems
        );

        $existTrackIds = $this->makeBuilder(trackIds: $trackIds)
            ->pluck('trackId')
            ->toArray();

        if ($existTrackIds) {
            throw new TraceAlreadyExistsException($existTrackIds);
        }

        $createdAt = new UTCDateTime(now());

        $documents = array_map(
            fn(TraceCreateParameters $parameters) => [
                'service'       => $parameters->service,
                'trackId'       => $parameters->trackId,
                'parentTrackId' => $parameters->parentTrackId,
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
     * @param array $trackIds
     *
     * @return Builder|Trace
     */
    private function makeBuilder(array $trackIds): Builder
    {
        return Trace::query()->whereIn('trackId', $trackIds);
    }
}
