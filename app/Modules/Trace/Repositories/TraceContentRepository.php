<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\Trace;
use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterDto;
use App\Modules\Trace\Repositories\Dto\TraceStringFieldDto;
use App\Modules\Trace\Repositories\Interfaces\TraceContentRepositoryInterface;
use App\Modules\Trace\Repositories\Services\TraceQueryBuilder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use MongoDB\Model\BSONDocument;

readonly class TraceContentRepository implements TraceContentRepositoryInterface
{
    public function __construct(
        private TraceQueryBuilder $traceQueryBuilder
    ) {
    }

    public function findTypes(
        array $serviceIds = [],
        ?string $text = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
    ): array {
        $builder = $this->traceQueryBuilder
            ->make(
                serviceIds: $serviceIds,
                loggedAtFrom: $loggedAtFrom,
                loggedAtTo: $loggedAtTo,
                data: $data,
                hasProfiling: $hasProfiling
            )
            ->when(
                $text,
                fn(Builder $query) => $query->where('type', 'like', "%$text%")
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
                '_id'   => '$type',
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

    public function findTags(
        array $serviceIds = [],
        ?string $text = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
    ): array
    {
        $builder = $this->traceQueryBuilder->make(
            serviceIds: $serviceIds,
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            types: $types,
            data: $data,
            hasProfiling: $hasProfiling
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
                '_id'   => '$tags',
                'count' => [
                    '$sum' => 1,
                ],
            ],
        ];

        if ($text) {
            $pipeline[] = [
                '$match' => [
                    '_id' => [
                        '$regex' => "^.*$text.*$",
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

    public function findStatuses(
        array $serviceIds = [],
        ?string $text = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $tags = [],
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
    ): array
    {
        $builder = $this->traceQueryBuilder
            ->make(
                serviceIds: $serviceIds,
                loggedAtFrom: $loggedAtFrom,
                loggedAtTo: $loggedAtTo,
                types: $types,
                tags: $tags,
                data: $data,
                hasProfiling: $hasProfiling
            )
            ->when(
                $text,
                fn(Builder $query) => $query->where('status', 'like', "%$text%")
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
                '_id'   => '$status',
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
