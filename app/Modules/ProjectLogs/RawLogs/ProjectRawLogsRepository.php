<?php

namespace App\Modules\ProjectLogs\RawLogs;

use App\Models\ProjectLogs\ProjectRawLog;
use App\Modules\ProjectLogs\RawLogs\Exceptions\ProjectRawLogsAlreadyExistsException;
use App\Modules\ProjectLogs\RawLogs\Parameters\ProjectRawLogCreateParameters;
use App\Modules\ProjectLogs\RawLogs\Parameters\ProjectRawLogCreateParametersList;
use Illuminate\Database\Eloquent\Builder;
use MongoDB\BSON\UTCDateTime;

class ProjectRawLogsRepository
{
    /**
     * @throws ProjectRawLogsAlreadyExistsException
     */
    public function create(ProjectRawLogCreateParameters $parameters): void
    {
        $this->createMany(
            (new ProjectRawLogCreateParametersList())->add($parameters)
        );
    }

    /**
     * @throws ProjectRawLogsAlreadyExistsException
     */
    public function createMany(ProjectRawLogCreateParametersList $parametersList): void
    {
        $parametersListItems = $parametersList->getItems();

        $trackIds = array_map(
            fn(ProjectRawLogCreateParameters $parameters) => $parameters->trackId,
            $parametersListItems
        );

        $existTrackIds = $this->makeBuilder(trackIds: $trackIds)
            ->pluck('trackId')
            ->toArray();

        if ($existTrackIds) {
            throw new ProjectRawLogsAlreadyExistsException($existTrackIds);
        }

        $createdAt = new UTCDateTime(now());

        $documents = array_map(
            fn(ProjectRawLogCreateParameters $parameters) => [
                'service'       => $parameters->service,
                'trackId'       => $parameters->trackId,
                'parentTrackId' => $parameters->parentTrackId,
                'type'          => $parameters->type->value,
                'tags'          => $parameters->tags,
                'data'          => $parameters->data,
                'loggedAt'      => new UTCDateTime($parameters->loggedAt),
                'createdAt'     => $createdAt,
            ],
            $parametersListItems
        );

        ProjectRawLog::collection()->insertMany($documents);
    }

    /**
     * @param array $trackIds
     *
     * @return Builder|ProjectRawLog
     */
    private function makeBuilder(array $trackIds): Builder
    {
        return ProjectRawLog::query()->whereIn('trackId', $trackIds);
    }
}
