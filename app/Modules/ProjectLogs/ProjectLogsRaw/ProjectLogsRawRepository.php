<?php

namespace App\Modules\ProjectLogs\ProjectLogsRaw;

use App\Models\Logs\RawLog;
use App\Modules\ProjectLogs\ProjectLogsRaw\Exceptions\RawLogsAlreadyExistsException;
use App\Modules\ProjectLogs\ProjectLogsRaw\Parameters\CreateProjectRawLogParameters;
use App\Modules\ProjectLogs\ProjectLogsRaw\Parameters\CreateProjectRawLogParametersList;
use MongoDB\BSON\UTCDateTime;

class ProjectLogsRawRepository
{
    /**
     * @throws RawLogsAlreadyExistsException
     */
    public function create(CreateProjectRawLogParameters $parameters): void
    {
        $this->createMany(
            (new CreateProjectRawLogParametersList())->add($parameters)
        );
    }

    /**
     * @throws RawLogsAlreadyExistsException
     */
    public function createMany(CreateProjectRawLogParametersList $parametersList): void
    {
        $parametersListItems = $parametersList->getItems();

        $trackIds = array_map(
            fn(CreateProjectRawLogParameters $parameters) => $parameters->trackId,
            $parametersListItems
        );

        $existTrackIds = RawLog::query()
            ->whereIn('trackId', $trackIds)
            ->pluck('trackId')
            ->toArray();

        if ($existTrackIds) {
            throw new RawLogsAlreadyExistsException($existTrackIds);
        }

        $createdAt = new UTCDateTime(now());

        $documents = array_map(
            fn(CreateProjectRawLogParameters $parameters) => [
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

        RawLog::collection()->insertMany($documents);
    }
}
