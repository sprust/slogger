<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\Trace;
use App\Models\Traces\TraceTree;
use App\Modules\Trace\Repositories\Interfaces\TraceTreeRepositoryInterface;
use Illuminate\Support\Carbon;
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

    public function deleteByIds(array $traceIds): int
    {
        return TraceTree::query()->whereIn('traceId', $traceIds)->delete();
    }

    public function deleteToLoggedAt(Carbon $to): void
    {
        TraceTree::query()->where('loggedAt', '<=', new UTCDateTime($to))->delete();
    }
}
