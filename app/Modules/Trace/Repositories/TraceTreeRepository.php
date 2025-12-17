<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceTree;
use App\Modules\Trace\Contracts\Repositories\TraceTreeRepositoryInterface;
use MongoDB\Model\BSONDocument;

class TraceTreeRepository implements TraceTreeRepositoryInterface
{
    private int $maxDepthForFindParent = 100;

    public function findParentTraceId(string $traceId): ?string
    {
        /** @var array{tid: string, ptid: string|null}|null $trace */
        $trace = TraceTree::query()
            ->select([
                'tid',
                'ptid',
            ])
            ->where('tid', $traceId)
            ->toBase()
            ->first();

        if (!$trace) {
            return null;
        }

        $parentTrace = $trace;

        if ($trace['ptid']) {
            $index = 0;

            while (++$index <= $this->maxDepthForFindParent) {
                if (!$parentTrace['ptid']) {
                    break;
                }

                /** @var array{tid: string, ptid: string|null}|null $currentParentTrace */
                $currentParentTrace = TraceTree::query()
                    ->select([
                        'tid',
                        'ptid',
                    ])
                    ->where('tid', $parentTrace['ptid'])
                    ->toBase()
                    ->first();

                if (!$currentParentTrace) {
                    break;
                }

                $parentTrace = $currentParentTrace;
            }
        }

        return $parentTrace['tid'];
    }

    public function findChainToParentTraceId(string $traceId): array
    {
        /** @var array{tid: string, ptid: string|null}|null $trace */
        $trace = TraceTree::query()
            ->select([
                'tid',
                'ptid',
            ])
            ->where('tid', $traceId)
            ->toBase()
            ->first();

        if (!$trace) {
            return [];
        }

        $chain = [];

        $parentTrace = $trace;

        if ($trace['ptid']) {
            $index = 0;

            while (++$index <= $this->maxDepthForFindParent) {
                if (!$parentTrace['ptid']) {
                    break;
                }

                /** @var array{tid: string, ptid: string|null}|null $currentParentTrace */
                $currentParentTrace = TraceTree::query()
                    ->select([
                        'tid',
                        'ptid',
                    ])
                    ->where('tid', $parentTrace['ptid'])
                    ->toBase()
                    ->first();

                if (!$currentParentTrace) {
                    break;
                }

                $parentTrace = $currentParentTrace;

                $chain[] = $parentTrace['tid'];
            }
        }

        return $chain;
    }

    public function findTraceIdsInTreeByParentTraceId(string $traceId): array
    {
        $traceTreesCollectionName = (new TraceTree())->getCollectionName();

        $childrenAggregation = TraceTree::collection()
            ->aggregate(
                [
                    [
                        '$graphLookup' => [
                            'from'             => $traceTreesCollectionName,
                            'startWith'        => '$tid',
                            'connectFromField' => 'tid',
                            'connectToField'   => 'ptid',
                            'as'               => 'children',
                            'maxDepth'         => $this->maxDepthForFindParent,
                        ],
                    ],
                    [
                        '$project' => [
                            'tid'      => 1,
                            'childIds' => [
                                '$concatArrays' => [
                                    [
                                        '$tid',
                                    ],
                                    [
                                        '$map' => [
                                            'input' => '$children',
                                            'as'    => 'children',
                                            'in'    => '$$children.tid',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                    [
                        '$match' => [
                            'tid' => $traceId,
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

        return array_map(
            fn(BSONDocument $item) => $item['childIds'],
            iterator_to_array($childrenAggregation)
        );
    }
}
