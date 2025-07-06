<?php

declare(strict_types=1);

namespace App\Modules\Trace\Contracts\Repositories;

use App\Modules\Trace\Entities\Trace\Tree\TraceTreeMapDepthObject;
use App\Modules\Trace\Parameters\CreateTraceTreeCacheParameters;

interface TraceTreeCacheRepositoryInterface
{
    public function deleteByParentTraceId(string $parentTraceId): void;

    /**
     * @param CreateTraceTreeCacheParameters[] $parametersList
     */
    public function createMany(string $parentTraceId, array $parametersList): void;

    /**
     * @param array<string, TraceTreeMapDepthObject> $depths
     */
    public function updateDepths(string $parentTraceId, array $depths): void;
}
