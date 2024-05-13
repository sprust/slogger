<?php

namespace App\Modules\TraceAggregator\Domain\Services;

use App\Modules\TraceAggregator\Domain\Entities\Objects\ProfilingItemDataObject;
use App\Modules\TraceAggregator\Domain\Entities\Objects\ProfilingItemObject;
use Illuminate\Support\Str;

class TraceProfilingBuilder
{
    private array $profiling;

    private array $map;

    public function build(array $profiling, ?string $call): array
    {
        $this->profiling = $profiling;

        $this->map = [];

        $result = [];

        $this->buildRecursive($result, $call ?: 'main()');

        return $result;
    }

    /**
     * @param ProfilingItemObject[] $parentCallables
     */
    private function buildRecursive(array &$parentCallables, string $calling): void
    {
        foreach ($this->profiling as $item) {
            if ($item['calling'] !== $calling) {
                continue;
            }

            $object = $this->makeObjectFromItem($item);

            $parentCallables[] = $object;

            if ($callableObjectId = $this->map[$item['callable']] ?? null) {
                $object->link = $callableObjectId;

                continue;
            }

            $this->map[$item['callable']] = $object->id;

            $this->buildRecursive($object->callables, $item['callable']);
        }
    }

    private function makeObjectFromItem(array $item): ProfilingItemObject
    {
        return new ProfilingItemObject(
            id: Str::uuid()->toString(),
            call: $item['callable'],
            data: array_map(
                fn(array $itemData) => new ProfilingItemDataObject(
                    name: $itemData['name'],
                    value: $itemData['value']
                ),
                $item['data']
            ),
            callables: [],
            link: null
        );
    }
}
