<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonCountObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupObject;
use App\Modules\Trace\Enums\TraceCompareByEnum;
use App\Modules\Trace\Enums\TraceGroupFieldEnum;
use App\Modules\Trace\Enums\TraceMetricFieldAggregatorEnum;
use App\Modules\Trace\Repositories\Services\PeriodicTraceService;
use App\Modules\Trace\Repositories\Services\TraceMetricAggregationFactory;
use App\Modules\Trace\Repositories\Services\TracePipelineBuilder;
use Illuminate\Support\Carbon;
use SConcur\Bson\UTCDateTime;

readonly class TraceGroupsRepository
{
    public function __construct(
        private TracePipelineBuilder $tracePipelineBuilder,
        private PeriodicTraceService $periodicTraceService,
        private TraceMetricAggregationFactory $aggregationFactory
    ) {
    }

    /**
     * @param TraceGroupFieldEnum[] $groupBy
     * @param int[]                 $serviceIds
     * @param string[]              $types
     * @param string[]              $tags
     * @param string[]              $statuses
     *
     * @return TraceGroupObject[]
     */
    public function findGroups(
        Carbon $loggedAtFrom,
        Carbon $loggedAtTo,
        array $groupBy,
        int $limit,
        array $serviceIds = [],
        array $types = [],
        array $tags = [],
        array $statuses = [],
        ?float $durationFrom = null,
        ?float $durationTo = null
    ): array {
        $groupId = [];

        foreach ($groupBy as $field) {
            $groupId[$field->value] = match ($field) {
                TraceGroupFieldEnum::Service  => '$sid',
                TraceGroupFieldEnum::Type     => '$tp',
                TraceGroupFieldEnum::Status   => '$st',
                TraceGroupFieldEnum::Hour     => ['$dateTrunc' => ['date' => '$lat', 'unit' => 'hour']],
                TraceGroupFieldEnum::Minute10 => [
                    '$dateTrunc' => ['date' => '$lat', 'unit' => 'minute', 'binSize' => 10],
                ],
            };
        }

        $timeField = null;

        foreach ($groupBy as $field) {
            if ($field === TraceGroupFieldEnum::Hour || $field === TraceGroupFieldEnum::Minute10) {
                $timeField = $field;
            }
        }

        $sort = is_null($timeField)
            ? ['count' => -1, '_id' => 1]
            : ["_id.$timeField->value" => 1, 'count' => -1, '_id' => 1];

        $documents = $this->aggregate(
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            match: $this->tracePipelineBuilder->make(
                serviceIds: $serviceIds,
                loggedAtFrom: $loggedAtFrom,
                loggedAtTo: $loggedAtTo,
                types: $types,
                tags: $tags,
                statuses: $statuses,
                durationFrom: $durationFrom,
                durationTo: $durationTo
            ),
            stages: [
                [
                    '$group' => [
                        '_id'     => $groupId,
                        'count'   => ['$sum' => 1],
                        'avg'     => ['$avg' => '$dur'],
                        'p95'     => $this->aggregationFactory->makeExpression(
                            aggregation: TraceMetricFieldAggregatorEnum::P95,
                            fieldName: 'dur'
                        ),
                        'max'     => ['$max' => '$dur'],
                        'example' => ['$top' => ['sortBy' => ['dur' => -1], 'output' => '$tid']],
                    ],
                ],
                ['$sort'  => $sort],
                ['$limit' => $limit],
            ]
        );

        return array_map(
            function (array $document) use ($timeField): TraceGroupObject {
                /** @var array<string, mixed> $id */
                $id = is_array($document['_id'] ?? null) ? $document['_id'] : [];

                $startedAt = is_null($timeField) ? null : ($id[$timeField->value] ?? null);

                return new TraceGroupObject(
                    serviceId: is_numeric($id['service'] ?? null) ? (int) $id['service'] : null,
                    type: is_string($id['type'] ?? null) ? $id['type'] : null,
                    status: is_string($id['status'] ?? null) ? $id['status'] : null,
                    startedAt: $startedAt instanceof UTCDateTime ? new Carbon($startedAt->toDateTime()) : null,
                    count: (int) ($document['count'] ?? 0),
                    durationAvg: $this->readFloat($document['avg'] ?? null),
                    durationP95: $this->readFloat($document['p95'] ?? null),
                    durationMax: $this->readFloat($document['max'] ?? null),
                    exampleTraceId: is_string($document['example'] ?? null) ? $document['example'] : null
                );
            },
            $documents
        );
    }

    /**
     * @param string[] $groupAStatuses
     * @param string[] $statuses
     * @param int[]    $serviceIds
     * @param string[] $types
     *
     * @return TraceGroupComparisonCountObject[]
     */
    public function compareGroups(
        Carbon $loggedAtFrom,
        Carbon $loggedAtTo,
        array $groupAStatuses,
        TraceCompareByEnum $by,
        ?string $dataKey,
        array $serviceIds = [],
        array $types = [],
        array $statuses = []
    ): array {
        $stages = [];

        if ($by === TraceCompareByEnum::Tag) {
            $stages[] = ['$unwind' => ['path' => '$tgs', 'preserveNullAndEmptyArrays' => true]];
        }

        $value = match ($by) {
            TraceCompareByEnum::Type    => '$tp',
            TraceCompareByEnum::Service => '$sid',
            TraceCompareByEnum::Tag     => '$tgs.nm',
            TraceCompareByEnum::Data    => '$dt.' . $dataKey,
        };

        $stages[] = [
            '$group' => [
                '_id'   => [
                    'inA'   => ['$in' => ['$st', array_values($groupAStatuses)]],
                    'value' => $value,
                ],
                'count' => ['$sum' => 1],
            ],
        ];

        $documents = $this->aggregate(
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            match: $this->tracePipelineBuilder->make(
                serviceIds: $serviceIds,
                loggedAtFrom: $loggedAtFrom,
                loggedAtTo: $loggedAtTo,
                types: $types,
                statuses: $statuses
            ),
            stages: $stages
        );

        return array_map(
            function (array $document): TraceGroupComparisonCountObject {
                /** @var array<string, mixed> $id */
                $id = is_array($document['_id'] ?? null) ? $document['_id'] : [];

                return new TraceGroupComparisonCountObject(
                    value: $this->readValue($id['value'] ?? null),
                    inGroupA: (bool) ($id['inA'] ?? false),
                    count: (int) ($document['count'] ?? 0)
                );
            },
            $documents
        );
    }

    /**
     * @param array<int, array<string, mixed>> $match
     * @param array<int, array<string, mixed>> $stages
     *
     * @return array<int, array<string, mixed>>
     */
    private function aggregate(Carbon $loggedAtFrom, Carbon $loggedAtTo, array $match, array $stages): array
    {
        $collectionNames = $this->periodicTraceService->detectCollectionNames(
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo
        );

        if (count($collectionNames) === 0) {
            return [];
        }

        $cursor = $this->periodicTraceService->aggregate(
            collectionName: $collectionNames[0],
            pipeline: [
                ...$match,
                ...array_map(
                    static fn(string $collectionName) => [
                        '$unionWith' => [
                            'coll'     => $collectionName,
                            'pipeline' => $match,
                        ],
                    ],
                    array_slice($collectionNames, 1)
                ),
                ...$stages,
            ]
        );

        $documents = [];

        foreach ($cursor as $document) {
            $documents[] = $document;
        }

        return $documents;
    }

    private function readFloat(mixed $value): ?float
    {
        if (is_null($value) || (is_array($value) && count($value) === 0)) {
            return null;
        }

        return round($this->aggregationFactory->readValue($value), 6);
    }

    private function readValue(mixed $value): string|int|float|bool|null
    {
        if (is_null($value) || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof UTCDateTime) {
            return new Carbon($value->toDateTime())->toIso8601ZuluString();
        }

        return (string) json_encode($value);
    }
}
