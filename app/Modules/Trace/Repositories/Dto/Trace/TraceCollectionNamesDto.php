<?php

namespace App\Modules\Trace\Repositories\Dto\Trace;

class TraceCollectionNamesDto
{
    /** @var array<string, string[]> */
    private array $traceCollections = [];

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
}
