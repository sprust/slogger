<?php

namespace App\Modules\TraceAggregator\Domain\Services;

use App\Modules\TraceAggregator\Domain\Entities\Objects\ProfilingItemObject;
use Illuminate\Support\Str;

class TraceProfilingBuilder
{
    private array $profiling;

    public function build(array $profiling): array
    {
        $this->profiling = $profiling;

        $result = [];

        $this->buildRecursive($result, 'main()');

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

            $this->buildRecursive($object->callables, $item['callable']);
        }
    }

    private function makeObjectFromItem(array $item): ProfilingItemObject
    {
        $itemData = $item['data'];

        return new ProfilingItemObject(
            id: Str::uuid()->toString(),
            call: $item['callable'],
            numberOfCalls: $itemData['numberOfCalls'],
            waitTimeInMs: $itemData['waitTimeInMs'],
            cpuTime: $itemData['cpuTime'],
            memoryUsageInBytes: $itemData['memoryUsageInBytes'],
            peakMemoryUsageInMb: $itemData['peakMemoryUsageInMb'],
            callables: [],
        );
    }
}
