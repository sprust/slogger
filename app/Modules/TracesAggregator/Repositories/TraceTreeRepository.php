<?php

namespace App\Modules\TracesAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\TracesAggregator\Dto\Objects\TraceTreeNodeObjects;
use App\Modules\TracesAggregator\Dto\Parameters\TraceMapFindParameters;
use App\Modules\TracesAggregator\Services\TraceTreeNodesBuilder;
use MongoDB\BSON\ObjectId;
use MongoDB\Model\BSONDocument;

class TraceTreeRepository implements TraceTreeRepositoryInterface
{
    private int $maxDepthForFindParent = 100;

    public function findTraces(TraceMapFindParameters $parameters): TraceTreeNodeObjects
    {
        /** @var Trace|null $trace */
        $trace = Trace::query()->where('traceId', $parameters->traceId)->first();

        if (!$trace) {
            return new TraceTreeNodeObjects(
                items: []
            );
        }

        $parentTrace = $this->findParentTrace($trace);

        $childrenAggregation = Trace::collection()
            ->aggregate(
                [
                    [
                        '$graphLookup' => [
                            'from'             => 'traces',
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

        $childrenIds = collect($childrenAggregation)->map(fn(BSONDocument $item) => $item['childIds']);

        $children = Trace::query()
            ->with([
                'service',
            ])
            ->whereIn('traceId', $childrenIds)
            ->get();

        $treeNodesBuilder = new TraceTreeNodesBuilder(
            parentTrace: $parentTrace,
            children: $children->collect()
        );

        return new TraceTreeNodeObjects(
            items: [
                $treeNodesBuilder->collect(),
            ]
        );
    }

    private function findParentTrace(Trace $trace): Trace
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
}
