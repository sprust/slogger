<?php

declare(strict_types=1);

namespace App\Modules\Logs\Repositories;

use App\Models\Logs\Log;
use App\Modules\Common\Entities\PaginationInfoObject;
use App\Modules\Logs\Entities\Log\LogLevelStatObject;
use App\Modules\Logs\Entities\Log\LogObject;
use App\Modules\Logs\Entities\Log\LogsPaginationObject;
use App\Modules\Logs\Parameters\CreateLogParameters;
use App\Modules\Logs\Parameters\FindLogsParameters;
use Illuminate\Support\Carbon;
use SConcur\Bson\Regex;
use SConcur\Bson\UTCDateTime;

readonly class LogRepository
{
    public function create(CreateLogParameters $parameters): string
    {
        $result = Log::sconcur()->insertOne([
            'level'     => $parameters->level,
            'message'   => $parameters->message,
            'context'   => $parameters->context,
            'channel'   => $parameters->channel,
            'loggedAt'  => new UTCDateTime($parameters->loggedAt),
            // Eloquent filled this from Log::CREATED_AT; without it writing the document
            // is what the repository does, so the timestamp is written here.
            'createdAt' => new UTCDateTime(now()),
        ]);

        return (string) $result->insertedId;
    }

    public function paginate(int $page, int $perPage, FindLogsParameters $parameters): LogsPaginationObject
    {
        $collection = Log::sconcur();

        $filter = [];

        if ($parameters->searchQuery) {
            // The search was a `like` between two wildcards, which the ORM turned into
            // exactly this: the term quoted so its own regex characters match themselves,
            // unanchored, case-insensitive.
            $filter['message'] = new Regex(preg_quote($parameters->searchQuery), 'i');
        }

        if ($parameters->level) {
            $filter['level'] = $parameters->level;
        }

        $total = $collection->countDocuments($filter);

        $items = [];

        foreach (
            $collection->find(
                filter: $filter,
                sort: ['loggedAt' => -1],
                limit: $perPage,
                skip: ($page - 1) * $perPage,
            ) as $document
        ) {
            $items[] = new LogObject(
                level: $document['level'],
                message: $document['message'],
                context: (array) $document['context'],
                channel: $document['channel'],
                loggedAt: new Carbon($document['loggedAt']->toDateTime())
            );
        }

        return new LogsPaginationObject(
            items: $items,
            paginationInfo: new PaginationInfoObject(
                total: $total,
                perPage: $perPage,
                currentPage: $page
            )
        );
    }

    /**
     * @param string[] $levels
     */
    public function findLevelStatBetween(Carbon $since, Carbon $until, array $levels): LogLevelStatObject
    {
        $collection = Log::sconcur();

        $filter = [
            'loggedAt' => [
                '$gt'  => new UTCDateTime($since),
                '$lte' => new UTCDateTime($until),
            ],
            'level'    => [
                '$in' => $levels,
            ],
        ];

        $count = $collection->countDocuments($filter);

        if ($count === 0) {
            return new LogLevelStatObject(count: 0, lastMessage: null);
        }

        $lastMessage = null;

        foreach (
            $collection->find(
                filter: $filter,
                projection: ['message' => 1],
                sort: ['loggedAt' => -1],
                limit: 1,
            ) as $document
        ) {
            $lastMessage = (string) $document['message'];
        }

        return new LogLevelStatObject(
            count: $count,
            lastMessage: $lastMessage
        );
    }
}
