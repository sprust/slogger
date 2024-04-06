<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Models\Traces\TraceTree;
use App\Modules\TraceAggregator\Domain\Entities\Objects\TraceTreeObjects;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceFindTreeParameters;
use App\Modules\TraceAggregator\Domain\Entities\Parameters\TraceTreeDeleteManyParameters;
use App\Modules\TraceAggregator\Domain\Exceptions\TreeTooLongException;
use App\Modules\TraceAggregator\Services\TraceTreeBuilder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Model\BSONDocument;

class TraceTreeRepository implements TraceTreeRepositoryInterface
{
    private int $maxDepthForFindParent = 100;

    public function insertMany(array $parametersList): void
    {
        $operations = [];

        $createdAt = new UTCDateTime(now());

        foreach ($parametersList as $parameters) {
            $operations[] = [
                'updateOne' => [
                    [
                        'traceId'       => $parameters->traceId,
                        'parentTraceId' => $parameters->parentTraceId,
                    ],
                    [
                        '$set'         => [
                            'traceId'       => $parameters->traceId,
                            'parentTraceId' => $parameters->parentTraceId,
                            'loggedAt'      => new UTCDateTime($parameters->loggedAt),
                        ],
                        '$setOnInsert' => [
                            'createdAt' => $createdAt,
                        ],
                    ],
                    [
                        'upsert' => true,
                    ],
                ],
            ];
        }

        TraceTree::collection()->bulkWrite($operations);
    }

    public function find(TraceFindTreeParameters $parameters): TraceTreeObjects
    {
        /** @var Trace|null $trace */
        $trace = Trace::query()->where('traceId', $parameters->traceId)->first();

        if (!$trace) {
            return new TraceTreeObjects(
                tracesCount: 0,
                items: []
            );
        }

        $parentTrace = $this->findParentTrace($trace);

        $childrenIds = $this->findTraceIdsInTreeByParentTraceId($parentTrace);

        $tracesCount = count($childrenIds) + 1;

        if ($tracesCount > 3000) {
            throw new TreeTooLongException(
                limit: 3000,
                current: $tracesCount
            );
        }

        $children = collect();

        foreach (collect($childrenIds)->chunk(300) as $childrenIdsChunk) {
            Trace::query()
                ->select([
                    '_id',
                    'serviceId',
                    'traceId',
                    'parentTraceId',
                    'type',
                    'status',
                    'tags',
                    'duration',
                    'memory',
                    'cpu',
                    'loggedAt',
                    'createdAt',
                    'updatedAt',
                ])
                ->with([
                    'service' => fn(BelongsTo $relation) => $relation->select([
                        'id',
                        'name',
                    ]),
                ])
                ->whereIn('traceId', $childrenIdsChunk)
                ->each(function (Trace $trace) use ($children) {
                    $children->add($trace);
                });
        }

        $treeNodesBuilder = new TraceTreeBuilder(
            parentTrace: $parentTrace,
            children: $children
        );

        unset($children);

        return new TraceTreeObjects(
            tracesCount: $tracesCount,
            items: [
                $treeNodesBuilder->collect(),
            ]
        );
    }

    /**
     * @return string[]
     */
    public function findTraceIdsInTreeByParentTraceId(Trace $parentTrace): array
    {
        $childrenAggregation = Trace::collection()
            ->aggregate(
                [
                    [
                        '$graphLookup' => [
                            'from'             => 'traceTrees',
                            'startWith'        => '$traceId',
                            'connectFromField' => 'traceId',
                            'connectToField'   => 'parentTraceId',
                            'as'               => 'children',
                            'maxDepth'         => $this->maxDepthForFindParent,
                        ],
                    ],
                    [
                        '$project' => [
                            'childIds' => [
                                '$concatArrays' => [
                                    [
                                        '$traceId',
                                    ],
                                    [
                                        '$map' => [
                                            'input' => '$children',
                                            'as'    => 'children',
                                            'in'    => '$$children.traceId',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        '$match' => [
                            '_id' => new ObjectId($parentTrace->_id),
                        ],
                    ],
                    [
                        '$unwind' => [
                            'path' => '$childIds',
                        ],
                    ],
                    [
                        '$match' => [
                            'childIds' => [
                                '$ne' => $parentTrace->traceId,
                            ],
                        ],
                    ],
                ]
            );

        return collect($childrenAggregation)
            ->map(fn(BSONDocument $item) => $item['childIds'])
            ->toArray();
    }

    public function findParentTrace(Trace $trace): Trace
    {
        $parentTrace = $trace;

        if ($trace->parentTraceId) {
            $index = 0;

            while (++$index <= $this->maxDepthForFindParent) {
                if (!$parentTrace->parentTraceId) {
                    break;
                }

                /** @var Trace|null $currentParentTrace */
                $currentParentTrace = Trace::query()
                    ->where('traceId', $parentTrace->parentTraceId)
                    ->first();

                if (!$currentParentTrace) {
                    break;
                }

                $parentTrace = $currentParentTrace;
            }
        }

        return $parentTrace;
    }

    public function deleteMany(TraceTreeDeleteManyParameters $parameters): void
    {
        TraceTree::query()->where('loggedAt', '<=', new UTCDateTime($parameters->to))->delete();
    }
}
