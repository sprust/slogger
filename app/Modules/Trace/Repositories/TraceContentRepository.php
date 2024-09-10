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
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
    ): array {
        $builder = $this->traceQueryBuilder
            ->make(
                serviceIds: $serviceIds,
                loggedAtFrom: $loggedAtFrom,
                loggedAtTo: $loggedAtTo,
                durationFrom: $durationFrom,
                durationTo: $durationTo,
                memoryFrom: $memoryFrom,
                memoryTo: $memoryTo,
                cpuFrom: $cpuFrom,
                cpuTo: $cpuTo,
                data: $data,
                hasProfiling: $hasProfiling
            )
            ->when(
                $text,
                fn(Builder $query) => $query->where('tp', 'like', "%$text%")
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
                '_id'   => '$tp',
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
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
    ): array {
        $builder = $this->traceQueryBuilder->make(
            serviceIds: $serviceIds,
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            types: $types,
            durationFrom: $durationFrom,
            durationTo: $durationTo,
            memoryFrom: $memoryFrom,
            memoryTo: $memoryTo,
            cpuFrom: $cpuFrom,
            cpuTo: $cpuTo,
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

        if ($text) {
            $pipeline[] = [
                '$match' => [
                    'tgs.nm' => [
                        '$regex' => "^.*$text.*$",
                    ],
                ],
            ];
        }

        $pipeline[] = [
            '$group' => [
                '_id'   => '$tgs.nm',
                'count' => [
                    '$sum' => 1,
                ],
            ],
        ];

        $pipeline[] = [
            '$unwind' => [
                'path' => '$_id',
            ],
        ];

        $pipeline[] = [
            '$group' => [
                '_id'   => '$_id',
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

    public function findStatuses(
        array $serviceIds = [],
        ?string $text = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $tags = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
    ): array {
        $builder = $this->traceQueryBuilder
            ->make(
                serviceIds: $serviceIds,
                loggedAtFrom: $loggedAtFrom,
                loggedAtTo: $loggedAtTo,
                types: $types,
                tags: $tags,
                durationFrom: $durationFrom,
                durationTo: $durationTo,
                memoryFrom: $memoryFrom,
                memoryTo: $memoryTo,
                cpuFrom: $cpuFrom,
                cpuTo: $cpuTo,
                data: $data,
                hasProfiling: $hasProfiling
            )
            ->when(
                $text,
                fn(Builder $query) => $query->where('st', 'like', "%$text%")
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
                '_id'   => '$st',
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
