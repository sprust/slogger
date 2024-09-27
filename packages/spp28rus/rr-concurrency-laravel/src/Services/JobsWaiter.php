<?php

namespace RrConcurrency\Services;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Carbon;
use RrConcurrency\Services\Dto\JobResultDto;

readonly class JobsWaiter
{
    public function __construct(private Repository $cache)
    {
    }

    public function finish(string $id, JobResultDto $result): void
    {
        $this->cache->put(
            key: $this->makeCacheKey($id),
            value: serialize($result),
            ttl: Carbon::now()->addMinute()
        );
    }

    public function result(string $id): ?JobResultDto
    {
        $cacheKey = $this->makeCacheKey($id);

        $serialized = $this->cache->get($cacheKey);

        if (!$serialized) {
            return null;
        }

        $this->cache->forget($cacheKey);

        return unserialize($serialized);
    }

    private function makeCacheKey(string $id): string
    {
        return "rr-concurrency:$id";
    }
}
