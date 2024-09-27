<?php

namespace RrConcurrency\Services;

use RrConcurrency\Services\Dto\JobResultDto;
use Spiral\Goridge\RPC\RPC;
use Spiral\RoadRunner\KeyValue\Factory;
use Spiral\RoadRunner\KeyValue\StorageInterface;

readonly class JobsWaiter
{
    private StorageInterface $storage;

    public function __construct()
    {
        $rpcConnection = sprintf(
            'tcp://%s:%s',
            config('rr-concurrency.rpc.host'),
            config('rr-concurrency.rpc.port')
        );

        $rpc = RPC::create($rpcConnection);

        $factory = new Factory($rpc);

        $this->storage = $factory->select(
            config('rr-concurrency.kv.storage-name')
        );
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
        return "rr-concurrency:$id";
    }
}
