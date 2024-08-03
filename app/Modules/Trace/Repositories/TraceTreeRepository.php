<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceTree;
use App\Modules\Trace\Repositories\Interfaces\TraceTreeRepositoryInterface;
use MongoDB\Model\BSONDocument;

class TraceTreeRepository implements TraceTreeRepositoryInterface
{
    private int $maxDepthForFindParent = 100;

    public function findTraceIdsInTreeByParentTraceId(string $traceId): array
    {
        $childrenAggregation = TraceTree::collection()
            ->aggregate(
                [
                    [
                        '$graphLookup' => [
                            'from'             => 'traceTreesView',
                            'startWith'        => '$traceId',
                            'connectFromField' => 'traceId',
                            'connectToField'   => 'parentTraceId',
                            'as'               => 'children',
                            'maxDepth'         => $this->maxDepthForFindParent,
                        ],
                    ],
                    [
                        '$project' => [
                            'traceId'  => 1,
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
                            'traceId' => $traceId,
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
                                '$ne' => $traceId,
                            ],
                        ],
                    ],
                ]
            );

        return collect($childrenAggregation)
            ->map(fn(BSONDocument $item) => $item['childIds'])
            ->toArray();
    }

    public function findParentTraceId(string $traceId): ?string
    {
        /** @var array|null $trace */
        $trace = TraceTree::query()
            ->select([
                'traceId',
                'parentTraceId',
            ])
            ->where('traceId', $traceId)
            ->toBase()
            ->first();

        if (!$trace) {
            return null;
        }

        $parentTrace = $trace;

        if ($trace['parentTraceId']) {
            $index = 0;

            while (++$index <= $this->maxDepthForFindParent) {
                if (!$parentTrace['parentTraceId']) {
                    break;
                }

                /** @var array|null $currentParentTrace */
                $currentParentTrace = TraceTree::query()
                    ->select([
                        'traceId',
                        'parentTraceId',
                    ])
                    ->where('traceId', $parentTrace['parentTraceId'])
                    ->toBase()
                    ->first();

                if (!$currentParentTrace) {
                    break;
                }

                $parentTrace = $currentParentTrace;
            }
        }

        return $parentTrace['traceId'];
    }
}
