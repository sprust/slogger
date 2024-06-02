<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\Trace;
use App\Modules\Trace\Domain\Entities\Parameters\TraceFindStatusesParameters;
use App\Modules\Trace\Domain\Entities\Parameters\TraceFindTagsParameters;
use App\Modules\Trace\Domain\Entities\Parameters\TraceFindTypesParameters;
use App\Modules\Trace\Repositories\Dto\TraceStringFieldDto;
use App\Modules\Trace\Repositories\Interfaces\TraceContentRepositoryInterface;
use App\Modules\Trace\Repositories\Services\TraceQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use MongoDB\Model\BSONDocument;

readonly class TraceContentRepository implements TraceContentRepositoryInterface
{
    public function __construct(
        private TraceQueryBuilder $traceQueryBuilder
    ) {
    }

    public function findTypes(TraceFindTypesParameters $parameters): array
    {
        $builder = $this->traceQueryBuilder
            ->make(
                serviceIds: $parameters->serviceIds,
                loggedAtFrom: $parameters->loggingPeriod?->from,
                loggedAtTo: $parameters->loggingPeriod?->to,
                data: $parameters->data,
                hasProfiling: $parameters->hasProfiling
            )
            ->when(
                $parameters->text,
                fn(Builder $query) => $query->where('type', 'like', "%$parameters->text%")
            );

        $match = $this->traceQueryBuilder->makeMqlMatchFromBuilder(
            builder: $builder
        );

        $pipeline = [];

        if ($match) {
            $pipeline[] = [
                '$match' => $match,
            ];
        }

        $pipeline[] = [
            '$group' => [
                '_id' => '$type',
                'count' => [
                    '$sum' => 1,
                ],
            ],
        ];

        $pipeline[] = [
            '$sort' => [
                'count' => -1,
                '_id'   => 1,
            ],
        ];

        $pipeline[] = [
            '$limit' => 50,
        ];

        $iterator = Trace::collection()->aggregate($pipeline);

        return collect($iterator)
            ->map(
                fn(BSONDocument $document) => new TraceStringFieldDto(
                    name: $document->_id,
                    count: $document->count
                )
            )
            ->toArray();
    }

    public function findTags(TraceFindTagsParameters $parameters): array
    {
        $builder = $this->traceQueryBuilder->make(
            serviceIds: $parameters->serviceIds,
            loggedAtFrom: $parameters->loggingPeriod?->from,
            loggedAtTo: $parameters->loggingPeriod?->to,
            types: $parameters->types,
            data: $parameters->data,
            hasProfiling: $parameters->hasProfiling
        );

        $match = $this->traceQueryBuilder->makeMqlMatchFromBuilder(
            builder: $builder
        );

        $pipeline = [];

        if ($match) {
            $pipeline[] = [
                '$match' => $match,
            ];
        }

        $pipeline[] = [
            '$unwind' => [
                'path' => '$tags',
            ],
        ];

        $pipeline[] = [
            '$group' => [
                '_id' => '$tags',
                'count' => [
                    '$sum' => 1,
                ],
            ],
        ];

        if ($parameters->text) {
            $pipeline[] = [
                '$match' => [
                    '_id' => [
                        '$regex' => "^.*$parameters->text.*$",
                    ],
                ],
            ];
        }

        $pipeline[] = [
            '$sort' => [
                'count' => -1,
                '_id'   => 1,
            ],
        ];

        $pipeline[] = [
            '$limit' => 50,
        ];

        $iterator = Trace::collection()->aggregate($pipeline);

        return collect($iterator)
            ->map(
                fn(BSONDocument $document) => new TraceStringFieldDto(
                    name: $document->_id,
                    count: $document->count
                )
            )
            ->toArray();
    }

    public function findStatuses(TraceFindStatusesParameters $parameters): array
    {
        $builder = $this->traceQueryBuilder
            ->make(
                serviceIds: $parameters->serviceIds,
                loggedAtFrom: $parameters->loggingPeriod?->from,
                loggedAtTo: $parameters->loggingPeriod?->to,
                types: $parameters->types,
                tags: $parameters->tags,
                data: $parameters->data,
                hasProfiling: $parameters->hasProfiling
            )
            ->when(
                $parameters->text,
                fn(Builder $query) => $query->where('status', 'like', "%$parameters->text%")
            );

        $match = $this->traceQueryBuilder->makeMqlMatchFromBuilder(
            builder: $builder
        );

        $pipeline = [];

        if ($match) {
            $pipeline[] = [
                '$match' => $match,
            ];
        }

        $pipeline[] = [
            '$group' => [
                '_id' => '$status',
                'count' => [
                    '$sum' => 1,
                ],
            ],
        ];

        $pipeline[] = [
            '$sort' => [
                'count' => -1,
                '_id'   => 1,
            ],
        ];

        $pipeline[] = [
            '$limit' => 50,
        ];

        $iterator = Trace::collection()->aggregate($pipeline);

        return collect($iterator)
            ->map(
                fn(BSONDocument $document) => new TraceStringFieldDto(
                    name: $document->_id,
                    count: $document->count
                )
            )
            ->toArray();
    }
}
