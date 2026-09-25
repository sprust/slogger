<?php

namespace Tests\Modules\Common\Infrastructure\Services;

use App\Modules\Common\Domain\Services\Mutex\MutexManagerInterface;
use ArrayObject;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use SConcur\Features\Sleeper\Sleeper;
use Tests\TestCase;

class MutexManagerConcurrencyTest extends TestCase
{
    use MutexTestTrait;

    private const string STORE = 'sconcur_redis';

    private MutexManagerInterface $manager;

    /**
     * @var list<string>
     */
    private array $keys = [];

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.mutex_store', self::STORE);

        $this->manager = $this->app->make(MutexManagerInterface::class);
    }

    protected function tearDown(): void
    {
        foreach ($this->keys as $key) {
            Cache::store(self::STORE)->lock("mutex:$key")->forceRelease();
        }

        parent::tearDown();
    }

    public function testOnlyOneCoroutineIsInsideAtATime(): void
    {
        $key = $this->makeKey();

        $state = new ArrayObject(['inside' => 0, 'maxInside' => 0, 'counter' => 0]);

        $callbacks = [];

        for ($index = 0; $index < 5; ++$index) {
            $callbacks[] = function () use ($key, $state): string {
                $mutex = $this->makeMutex($key, waitForBlockSec: 10);

                $this->manager->lock($mutex);

                try {
                    ++$state['inside'];

                    $state['maxInside'] = max($state['maxInside'], $state['inside']);

                    $counter = $state['counter'];

                    Sleeper::usleep(20_000);

                    $state['counter'] = $counter + 1;

                    --$state['inside'];
                } finally {
                    $this->manager->unlock($mutex);
                }

                return 'done';
            };
        }

        $this->assertSame(array_fill(0, 5, 'done'), $this->runCoroutines(...$callbacks));
        $this->assertSame(1, $state['maxInside']);
        $this->assertSame(5, $state['counter']);
        $this->assertFalse($this->isHeld($key));
    }

    public function testAWaiterEntersOnceTheHolderUnlocks(): void
    {
        $key = $this->makeKey();

        $order = new ArrayObject();

        $this->runCoroutines(
            function () use ($key, $order): string {
                $mutex = $this->makeMutex($key);

                $this->manager->lock($mutex);

                try {
                    $order[] = 'holder in';

                    Sleeper::usleep(100_000);

                    $order[] = 'holder out';
                } finally {
                    $this->manager->unlock($mutex);
                }

                return 'holder';
            },
            function () use ($key, $order): string {
                Sleeper::usleep(10_000);

                $mutex = $this->makeMutex($key, waitForBlockSec: 5);

                $this->manager->lock($mutex);

                try {
                    $order[] = 'waiter in';
                } finally {
                    $this->manager->unlock($mutex);
                }

                return 'waiter';
            }
        );

        $this->assertSame(['holder in', 'holder out', 'waiter in'], $order->getArrayCopy());
    }

    public function testAWaiterGivesUpWhenTheHolderOutlastsTheWait(): void
    {
        $key = $this->makeKey();

        $results = $this->runCoroutines(
            function () use ($key): string {
                $mutex = $this->makeMutex($key);

                $this->manager->lock($mutex);

                try {
                    Sleeper::usleep(1_500_000);
                } finally {
                    $this->manager->unlock($mutex);
                }

                return 'held';
            },
            function () use ($key): string {
                Sleeper::usleep(10_000);

                return $this->tryLock($this->manager, $this->makeMutex($key, waitForBlockSec: 1));
            }
        );

        $this->assertSame(['held', 'timeout'], $results);
    }

    public function testASiblingDoesNotEnterAMutexHeldAcrossASuspension(): void
    {
        $key = $this->makeKey();

        $results = $this->runCoroutines(
            function () use ($key): string {
                $mutex = $this->makeMutex($key);

                $this->manager->lock($mutex);
                $this->manager->lock($mutex);
                $this->manager->unlock($mutex);

                try {
                    Sleeper::usleep(100_000);
                } finally {
                    $this->manager->unlock($mutex);
                }

                return 'held';
            },
            function () use ($key): string {
                Sleeper::usleep(10_000);

                return $this->tryLock($this->manager, $this->makeMutex($key, waitForBlockSec: 0));
            }
        );

        $this->assertSame(['held', 'timeout'], $results);
        $this->assertFalse($this->isHeld($key));
    }

    public function testDifferentKeysDoNotWaitForEachOther(): void
    {
        $state = new ArrayObject(['inside' => 0, 'maxInside' => 0]);

        $callbacks = [];

        foreach ([$this->makeKey(), $this->makeKey()] as $key) {
            $callbacks[] = function () use ($key, $state): string {
                $mutex = $this->makeMutex($key, waitForBlockSec: 0);

                $this->manager->lock($mutex);

                try {
                    ++$state['inside'];

                    $state['maxInside'] = max($state['maxInside'], $state['inside']);

                    Sleeper::usleep(50_000);

                    --$state['inside'];
                } finally {
                    $this->manager->unlock($mutex);
                }

                return 'done';
            };
        }

        $this->assertSame(['done', 'done'], $this->runCoroutines(...$callbacks));
        $this->assertSame(2, $state['maxInside']);
    }

    public function testALockLeftByAFinishedCoroutineExpires(): void
    {
        $key = $this->makeKey();

        $results = $this->runCoroutines(
            function () use ($key): string {
                $this->manager->lock($this->makeMutex($key, maxLockSec: 1));

                return 'abandoned';
            },
            function () use ($key): string {
                Sleeper::usleep(10_000);

                return $this->tryLock($this->manager, $this->makeMutex($key, waitForBlockSec: 3));
            }
        );

        $this->assertSame(['abandoned', 'locked'], $results);
    }

    private function makeKey(): string
    {
        $key = 'test:' . Str::uuid()->toString();

        $this->keys[] = $key;

        return $key;
    }

    private function isHeld(string $key): bool
    {
        return $this->isHeldIn(self::STORE, $key);
    }
}
