<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawIterator;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeStringableObject;
use App\Modules\Trace\Parameters\CreateTraceTreeCacheParameters;
use App\Modules\Trace\Parameters\TraceTreeDepthParameters;
use App\Modules\Trace\Repositories\Dto\Trace\TraceTreeServiceDto;

interface TraceTreeCacheRepositoryInterface
{
    public function has(string $parentTraceId): bool;

    public function deleteByParentTraceId(string $parentTraceId): void;

    /**
     * @param CreateTraceTreeCacheParameters[] $parametersList
     */
    public function createMany(string $parentTraceId, array $parametersList): void;

    /**
     * @param array<string, TraceTreeDepthParameters> $depths
     */
    public function updateDepths(string $parentTraceId, array $depths): void;

    public function findMany(string $parentTraceId): TraceTreeRawIterator;

    /**
     * @return TraceTreeServiceDto[]
     */
    public function findServices(string $parentTraceId): array;

    /**
     * @return TraceTreeStringableObject[]
     */
    public function findTypes(string $parentTraceId): array;

    /**
     * @return TraceTreeStringableObject[]
     */
    public function findTags(string $parentTraceId): array;

    /**
     * @return TraceTreeStringableObject[]
     */
    public function findStatuses(string $parentTraceId): array;

    public function findCount(string $parentTraceId): int;
}
