<?php

namespace Tests\Modules\Common\Infrastructure\Services;

use App\Modules\Common\Domain\Exceptions\MutexLockTimeoutException;
use App\Modules\Common\Domain\Services\Mutex\AbstractMutex;
use App\Modules\Common\Domain\Services\Mutex\MutexManagerInterface;
use Closure;
use Illuminate\Support\Facades\Cache;
use SConcur\WaitGroup;

trait MutexTestTrait
{
    protected function makeMutex(
        string $key,
        int $waitForBlockSec = 1,
        int $maxLockSec = 10,
        ?Closure $afterLock = null,
        ?Closure $beforeRelease = null
    ): AbstractMutex {
        return new readonly class($key, $waitForBlockSec, $maxLockSec, $afterLock, $beforeRelease) extends AbstractMutex {
            public function __construct(
                private string $key,
                private int $waitForBlockSec,
                private int $maxLockSec,
                private ?Closure $afterLock,
                private ?Closure $beforeRelease
            ) {
            }

            public function getKey(): string
            {
                return $this->key;
            }

            public function getMaxLockSec(): int
            {
                return $this->maxLockSec;
            }

            public function getWaitForBlockSec(): int
            {
                return $this->waitForBlockSec;
            }

            public function getAfterLockHandler(): ?Closure
            {
                return $this->afterLock;
            }

            public function getBeforeReleaseHandler(): ?Closure
            {
                return $this->beforeRelease;
            }
        };
    }

    protected function tryLock(MutexManagerInterface $manager, AbstractMutex $mutex): string
    {
        try {
            $manager->lock($mutex);
        } catch (MutexLockTimeoutException) {
            return 'timeout';
        }

        return 'locked';
    }

    protected function isHeldIn(string $store, string $key): bool
    {
        $probe = Cache::store($store)->lock("mutex:$key", 10);

        if ($probe->get()) {
            $probe->release();

            return false;
        }

        return true;
    }

    /**
     * @param Closure(): mixed ...$callbacks
     *
     * @return list<mixed>
     */
    protected function runCoroutines(Closure ...$callbacks): array
    {
        $waitGroup = WaitGroup::create();

        $keys = [];

        foreach ($callbacks as $callback) {
            $keys[] = $waitGroup->add($callback);
        }

        $results = $waitGroup->waitResults();

        return array_map(
            static fn(string $key): mixed => $results[$key],
            $keys
        );
    }
}
