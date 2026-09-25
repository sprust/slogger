<?php

declare(strict_types=1);

namespace App\Modules\Common\Infrastructure\Services;

use App\Modules\Common\Domain\Exceptions\MutexLockTimeoutException;
use App\Modules\Common\Domain\Services\Mutex\AbstractMutex;
use App\Modules\Common\Domain\Services\Mutex\MutexManagerInterface;
use Fiber;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Contracts\Foundation\Application;
use LogicException;
use SConcur\Context\Context;
use Throwable;

readonly class MutexManager implements MutexManagerInterface
{
    private LockProvider $store;

    public function __construct(
        private Application $app,
        CacheFactory $cacheFactory
    ) {
        $store = $cacheFactory->store(config('cache.mutex_store'))->getStore();

        if (!$store instanceof LockProvider) {
            throw new LogicException(
                sprintf('Cache store [%s] cannot hold a mutex', $store::class)
            );
        }

        $this->store = $store;
    }

    /**
     * @throws Throwable
     */
    public function lock(AbstractMutex $mutex): void
    {
        $key = $mutex->getKey();

        $holder = $this->findOwnHolder($key);

        if ($holder !== null) {
            $this->saveHolder(
                key: $key,
                lock: $holder->lock,
                level: $holder->level + 1
            );

            return;
        }

        $lock = $this->store->lock("mutex:$key", $mutex->getMaxLockSec());

        try {
            $lock->block($mutex->getWaitForBlockSec());
        } catch (LockTimeoutException) {
            throw new MutexLockTimeoutException($key);
        }

        $afterLockHandler = $mutex->getAfterLockHandler();

        if ($afterLockHandler !== null) {
            try {
                $this->app->call($afterLockHandler);
            } catch (Throwable $exception) {
                $lock->release();

                throw $exception;
            }
        }

        $this->saveHolder(key: $key, lock: $lock, level: 1);
    }

    public function unlock(AbstractMutex $mutex): void
    {
        $key = $mutex->getKey();

        $holder = $this->findOwnHolder($key);

        if ($holder === null) {
            return;
        }

        $level = $holder->level - 1;

        if ($level > 0) {
            $this->saveHolder(key: $key, lock: $holder->lock, level: $level);

            return;
        }

        try {
            $beforeReleaseHandler = $mutex->getBeforeReleaseHandler();

            if ($beforeReleaseHandler !== null) {
                $this->app->call($beforeReleaseHandler);
            }
        } finally {
            $holder->lock->release();

            Context::current()->forget($this->makeContextKey($key));
        }
    }

    private function findOwnHolder(string $key): ?MutexHolder
    {
        $holder = Context::current()->find($this->makeContextKey($key));

        if (!$holder instanceof MutexHolder || $holder->fiberId !== $this->currentFiberId()) {
            return null;
        }

        return $holder;
    }

    private function saveHolder(string $key, Lock $lock, int $level): void
    {
        Context::current()->set(
            key: $this->makeContextKey($key),
            value: new MutexHolder(
                lock: $lock,
                level: $level,
                fiberId: $this->currentFiberId()
            ),
            replace: true
        );
    }

    private function makeContextKey(string $key): string
    {
        return "mutex:$key";
    }

    private function currentFiberId(): int
    {
        $fiber = Fiber::getCurrent();

        return $fiber === null ? 0 : spl_object_id($fiber);
    }
}
