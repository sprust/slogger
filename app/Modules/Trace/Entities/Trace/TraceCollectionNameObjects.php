<?php

namespace App\Modules\Trace\Entities\Trace;

class TraceCollectionNameObjects
{
    /** @var array<string, string[]> */
    private array $traceCollections = [];

    /**
     * @param string[] $traceIds
     */
    public function add(string $collectionName, array $traceIds): void
    {
        $this->traceCollections[$collectionName] = $traceIds;
    }

    /**
     * @return array<string, string[]>
     */
    public function get(): array
    {
        return $this->traceCollections;
    }

    public function count(): int
    {
        return count($this->traceCollections);
    }
}
