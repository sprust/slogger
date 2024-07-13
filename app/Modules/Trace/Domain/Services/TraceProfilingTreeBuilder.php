<?php

namespace App\Modules\Trace\Domain\Services;

use App\Modules\Trace\Domain\Entities\Objects\Profiling\ProfilingItemDataObject;
use App\Modules\Trace\Domain\Entities\Objects\Profiling\Tree\ProfilingTreeNodeObject;
use App\Modules\Trace\Domain\Entities\Objects\Profiling\Tree\ProfilingTreeObject;
use App\Modules\Trace\Repositories\Dto\Profiling\TraceProfilingDataDto;
use App\Modules\Trace\Repositories\Dto\Profiling\TraceProfilingDto;
use App\Modules\Trace\Repositories\Dto\Profiling\TraceProfilingItemDto;
use Illuminate\Support\Str;

class TraceProfilingTreeBuilder
{
    private int $id;

    /** @var array<string, int> */
    private array $stackIds;

    /** @var array<string, int|float> */
    private array $maxDataValues;

    public function __construct(
        private readonly TraceProfilingDto $profiling,
        private readonly ?string $caller = null,
        public ?array $excludedCallers = null
    ) {
    }

    public function build(): ProfilingTreeObject
    {
        $this->id = 1;

        $this->stackIds = [];

        $this->maxDataValues = [];

        $rootCaller = $this->caller ?? $this->profiling->mainCaller;

        $result = new ProfilingTreeObject(
            nodes: [
                new ProfilingTreeNodeObject(
                    id: $this->id++,
                    calling: $rootCaller,
                    data: [],
                    children: $this->buildRecursive(
                        $this->filterByCaller($rootCaller)
                    )
                ),
            ],
        );

        $this->fillMaxDataValuesRecursive($result->nodes);
        $this->fillMaxDataValuesInResultRecursive($result->nodes);

        return $result;
    }

    /**
     * @param TraceProfilingItemDto[] $items
     *
     * @return ProfilingTreeNodeObject[]
     */
    private function buildRecursive(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            if ($this->excludedCallers && Str::is($this->excludedCallers, $item->callable)) {
                continue;
            }

            $stackKey = "$item->calling:$item->callable";

            $id = $this->id++;

            if (array_key_exists($stackKey, $this->stackIds)) {
                $treeNode = new ProfilingTreeNodeObject(
                    id: $id,
                    calling: $item->callable,
                    data: $this->transportData($item->data),
                    recursionNodeId: $this->stackIds[$stackKey],
                );
            } else {
                $this->stackIds[$stackKey] = $id;

                $treeNode = new ProfilingTreeNodeObject(
                    id: $id,
                    calling: $item->callable,
                    data: $this->transportData($item->data),
                    children: $this->buildRecursive(
                        items: $this->filterByCaller($item->callable),
                    ),
                );
            }

            $result[] = $treeNode;
        }

        return $result;
    }

    /**
     * @return TraceProfilingItemDto[]
     */
    private function filterByCaller(string $callable): array
    {
        return array_filter(
            $this->profiling->items,
            fn(TraceProfilingItemDto $item) => $item->calling === $callable,
        );
    }

    /**
     * @param TraceProfilingDataDto[] $dataItems
     *
     * @return ProfilingItemDataObject[]
     */
    private function transportData(array $dataItems): array
    {
        return array_map(
            fn(TraceProfilingDataDto $dto) => new ProfilingItemDataObject(
                name: $dto->name,
                value: $dto->value,
                weightPercent: 0,
            ),
            $dataItems
        );
    }

    /**
     * @param ProfilingTreeNodeObject[] $treeNodes
     */
    private function fillMaxDataValuesRecursive(array $treeNodes): void
    {
        foreach ($treeNodes as $treeNode) {
            foreach ($treeNode->data as $dataItem) {
                $this->maxDataValues[$dataItem->name] ??= 0;
                $this->maxDataValues[$dataItem->name] = max($this->maxDataValues[$dataItem->name], $dataItem->value);
            }

            if (empty($treeNode->children)) {
                continue;
            }

            $this->fillMaxDataValuesRecursive($treeNode->children);
        }
    }

    /**
     * @param ProfilingTreeNodeObject[] $treeNodes
     */
    private function fillMaxDataValuesInResultRecursive(array $treeNodes): void
    {
        foreach ($treeNodes as $treeNode) {
            foreach ($treeNode->data as $dataItem) {
                $dataItem->weightPercent = $this->maxDataValues[$dataItem->name] !== 0
                    ? round(($dataItem->value / $this->maxDataValues[$dataItem->name]) * 100, 4)
                    : 0;
            }

            if (empty($treeNode->children)) {
                continue;
            }

            $this->fillMaxDataValuesInResultRecursive($treeNode->children);
        }
    }
}
