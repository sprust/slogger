<?php

namespace App\Modules\TraceAggregator\Repositories;

use App\Models\Traces\Trace;
use App\Modules\TraceAggregator\Repositories\Interfaces\TraceTreeRepositoryInterface;
use MongoDB\Model\BSONDocument;

class TraceTreeRepository implements TraceTreeRepositoryInterface
{
    private int $maxDepthForFindParent = 100;

    public function findTraceIdsInTreeByParentTraceId(string $traceId): array
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
        $trace = Trace::query()
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

                /** @var Trace|null $currentParentTrace */
                $currentParentTrace = Trace::query()
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
