<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Mcp\Entities\Bridges\McpIndexStatusObject;
use App\Modules\Mcp\Enums\McpIndexStatusEnum;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceDynamicIndexStatsAction;
use App\Modules\Trace\Entities\Trace\TraceIndexInfoObject;

readonly class FindMcpIndexStatusAction
{
    public function __construct(
        private FindTraceDynamicIndexAction $findTraceDynamicIndexAction,
        private FindTraceDynamicIndexStatsAction $findTraceDynamicIndexStatsAction
    ) {
    }

    public function handle(string $indexId): McpIndexStatusObject
    {
        $index = $this->findTraceDynamicIndexAction->handle($indexId);

        if (is_null($index)) {
            return new McpIndexStatusObject(
                indexId: $indexId,
                status: McpIndexStatusEnum::NotFound,
                progress: null,
                error: null
            );
        }

        if (!is_null($index->error)) {
            return new McpIndexStatusObject(
                indexId: $indexId,
                status: McpIndexStatusEnum::Error,
                progress: null,
                error: $index->error
            );
        }

        if (!$index->inProcess) {
            return new McpIndexStatusObject(
                indexId: $indexId,
                status: McpIndexStatusEnum::Ready,
                progress: null,
                error: null
            );
        }

        $inProcess = array_filter(
            $this->findTraceDynamicIndexStatsAction->handle()->indexesInProcess,
            static fn(TraceIndexInfoObject $info) => $info->name === $index->indexName
                && in_array($info->collectionName, $index->collectionNames, true)
        );

        $collectionsCount = count($index->collectionNames);

        $progress = (count($inProcess) === 0 || $collectionsCount === 0)
            ? null
            : round(
                array_sum(array_map(static fn(TraceIndexInfoObject $info) => $info->progress, $inProcess))
                / 100
                / $collectionsCount,
                2
            );

        return new McpIndexStatusObject(
            indexId: $indexId,
            status: McpIndexStatusEnum::Building,
            progress: $progress,
            error: null
        );
    }
}
