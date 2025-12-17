<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeRawIterator;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeStringableObject;
use App\Modules\Trace\Parameters\CreateTraceTreeCacheParameters;
use App\Modules\Trace\Repositories\Dto\Trace\TraceTreeServiceDto;

interface TraceTreeCacheRepositoryInterface
{
    public function has(string $rootTraceId): bool;

    public function delete(string $rootTraceId): void;

    /**
     * @param CreateTraceTreeCacheParameters[] $parametersList
     */
    public function createMany(string $rootTraceId, array $parametersList): void;

    public function findMany(string $rootTraceId): TraceTreeRawIterator;

    /**
     * @return TraceTreeServiceDto[]
     */
    public function findServices(string $rootTraceId): array;

    /**
     * @return TraceTreeStringableObject[]
     */
    public function findTypes(string $rootTraceId): array;

    /**
     * @return TraceTreeStringableObject[]
     */
    public function findTags(string $rootTraceId): array;

    /**
     * @return TraceTreeStringableObject[]
     */
    public function findStatuses(string $rootTraceId): array;

    public function findCount(string $rootTraceId): int;
}
