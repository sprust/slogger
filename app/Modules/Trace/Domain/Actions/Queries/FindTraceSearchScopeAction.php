<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Entities\Trace\TraceSearchScopeObject;
use App\Modules\Trace\Parameters\TraceFindParameters;
use App\Modules\Trace\Repositories\TraceRepository;
use App\Modules\Trace\Repositories\TraceTreeRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use App\Services\Clickhouse\ClickhouseQueryException;

readonly class FindTraceSearchScopeAction
{
    public function __construct(private TraceRepository $traceRepository, private TraceTreeRepository $traceTreeRepository, private TraceTreeCacheRepository $traceTreeCacheRepository)
    {
    }

    /** @throws ClickhouseQueryException */
    public function handle(TraceFindParameters $parameters): TraceSearchScopeObject
    {
        $traceIds        = null;
        $treeRootTraceId = null;

        if ($parameters->traceId) {
            $traceDto = $this->traceRepository->findOneDetailByTraceId($parameters->traceId);

            if (!$traceDto) {
                return new TraceSearchScopeObject(
                    traceIds: null,
                    treeRootTraceId: null,
                    traceMissing: true,
                );
            }

            if (!$parameters->allTracesInTree) {
                $traceIds = [
                    $parameters->traceId,
                ];
            } else {
                $parentTraceId = $this->traceTreeRepository->findParentTraceId($traceDto->traceId);

                if ($parentTraceId === null) {
                    $traceIds = [];
                } elseif ($this->traceTreeCacheRepository->existsByRootTraceId($parentTraceId)) {
                    $treeRootTraceId = $parentTraceId;
                    $traceIds        = [
                        $parentTraceId,
                    ];
                } else {
                    $traceIds[]   = $parentTraceId;
                    $treeTraceIds = $this->traceTreeRepository->findTraceIdsInTreeByParentTraceId(
                        traceId: $parentTraceId,
                        batchCount: 300,
                    );

                    foreach ($treeTraceIds as $treeTraceIdsChunk) {
                        foreach ($treeTraceIdsChunk as $treeTraceId) {
                            $traceIds[] = $treeTraceId;
                        }
                    }
                }
            }
        }

        return new TraceSearchScopeObject(
            traceIds: $traceIds,
            treeRootTraceId: $treeRootTraceId,
            traceMissing: false,
        );
    }
}
