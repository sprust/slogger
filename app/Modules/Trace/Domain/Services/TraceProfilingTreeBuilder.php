<?php

namespace App\Modules\Trace\Domain\Services;

use App\Modules\Trace\Domain\Entities\Objects\Profiling\ProfilingItemObject;
use App\Modules\Trace\Domain\Entities\Objects\Profiling\ProfilingObject;
use App\Modules\Trace\Domain\Entities\Objects\Profiling\Tree\ProfilingTreeNodeObject;
use App\Modules\Trace\Domain\Entities\Objects\Profiling\Tree\ProfilingTreeObject;

class TraceProfilingTreeBuilder
{
    private int $id;

    /** @var array<string, int> */
    private array $stackIds;

    public function __construct(
        private readonly ProfilingObject $profiling
    ) {
    }

    public function build(): ProfilingTreeObject
    {
        $this->id = 1;

        $this->stackIds = [];

        return new ProfilingTreeObject(
            nodes: [
                new ProfilingTreeNodeObject(
                    id: $this->id++,
                    calling: $this->profiling->mainCaller,
                    data: [],
                    children: $this->buildRecursive(
                        items: $this->filterByCaller(
                            $this->profiling->mainCaller
                        )
                    )
                ),
            ],
        );
    }

    /**
     * @param ProfilingItemObject[] $items
     *
     * @return ProfilingItemObject[]
     */
    private function buildRecursive(array $items): array
    {
        $result = [];

        foreach ($items as $item) {
            $stackKey = "$item->calling:$item->callable";

            $id = $this->id++;

            if (array_key_exists($stackKey, $this->stackIds)) {
                $treeNode = new ProfilingTreeNodeObject(
                    id: $id,
                    calling: $item->callable,
                    data: $item->data,
                    recursionNodeId: $this->stackIds[$stackKey],
                );
            } else {
                $this->stackIds[$stackKey] = $id;

                $treeNode = new ProfilingTreeNodeObject(
                    id: $id,
                    calling: $item->callable,
                    data: $item->data,
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
     * @return ProfilingItemObject[]
     */
    private function filterByCaller(string $callable): array
    {
        return array_filter(
            $this->profiling->items,
            fn(ProfilingItemObject $item) => $item->calling === $callable,
        );
    }
}
