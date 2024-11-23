<?php

namespace RrParallel\Services\Drivers\Roadrunner;

use RrParallel\Services\Dto\JobResultDto;
use Spiral\RoadRunner\KeyValue\Factory;
use Spiral\RoadRunner\KeyValue\StorageInterface;

readonly class JobsWaiter
{
    private StorageInterface $storage;

    public function __construct(
        RpcFactory $rpcFactory,
        string $storageName,
    ) {
        $factory = new Factory(
            $rpcFactory->get()
        );

        $this->storage = $factory->select($storageName);
    }

    public function finish(string $id, JobResultDto $result): void
    {
        $this->storage->set(
            key: $this->makeCacheKey($id),
            value: serialize($result)
        );
    }

    public function result(string $id): ?JobResultDto
    {
        $cacheKey = $this->makeCacheKey($id);

        $serialized = $this->storage->get($cacheKey);

        if (!$serialized) {
            return null;
        }

        $this->storage->delete($cacheKey);

        return unserialize($serialized);
    }

    private function makeCacheKey(string $id): string
    {
        return "rr-parallel-result:$id";
    }
}
