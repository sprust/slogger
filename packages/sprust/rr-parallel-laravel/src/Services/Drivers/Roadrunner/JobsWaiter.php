<?php

namespace RrParallel\Services\Drivers\Roadrunner;

use Exception;
use Psr\SimpleCache\InvalidArgumentException;
use RrParallel\Services\Dto\JobResultDto;
use Spiral\RoadRunner\KeyValue\Factory;
use Spiral\RoadRunner\KeyValue\StorageInterface;

readonly class JobsWaiter
{
    private StorageInterface $keyValueStorage;

    public function __construct(RpcFactory $rpcFactory, string $storageName)
    {
        $factory = new Factory(
            $rpcFactory->get()
        );

        $this->keyValueStorage = $factory->select($storageName);
    }

    /**
     * @throws InvalidArgumentException
     */
    public function start(string $id): void
    {
        $this->keyValueStorage->set(
            key: $this->makeCacheKey($id),
            value: serialize([
                'pid'      => getmypid(),
                'finished' => false,
                'result'   => null,
            ]),
            ttl: $this->makeTtl()
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function finish(string $id, JobResultDto $result): void
    {
        $this->keyValueStorage->set(
            key: $this->makeCacheKey($id),
            value: serialize([
                'pid'      => getmypid(),
                'finished' => true,
                'result'   => $result,
            ]),
            ttl: $this->makeTtl()
        );
    }

    /**
     * @throws InvalidArgumentException
     */
    public function result(string $id): ?JobResultDto
    {
        $cacheKey = $this->makeCacheKey($id);

        $serialized = $this->keyValueStorage->get($cacheKey);

        if (!$serialized) {
            return null;
        }

        $result = unserialize($serialized);

        $jobWorkerPid = $result['pid'];

        $finished = $result['finished'];

        if (!$finished) {
            if (!file_exists("/proc/$jobWorkerPid")) {
                return new JobResultDto(
                    exception: new Exception(
                        "Process [$jobWorkerPid] was killed"
                    )
                );
            }

            return null;
        }

        $this->keyValueStorage->delete($cacheKey);

        return $result['result'];
    }

    private function makeCacheKey(string $id): string
    {
        return "rr-parallel-result:$id";
    }

    private function makeTtl(): int
    {
        return time() + 60;
    }
}
