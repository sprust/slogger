<?php

namespace Tests\Modules\Common\Infrastructure\Services;

use App\Modules\Common\Domain\Exceptions\MutexLockTimeoutException;
use App\Modules\Common\Domain\Services\Mutex\MutexManagerInterface;
use ArrayObject;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class MutexManagerTest extends TestCase
{
    use MutexTestTrait;

    private MutexManagerInterface $manager;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('cache.mutex_store', 'array');

        $this->manager = $this->app->make(MutexManagerInterface::class);
    }

    public function testALockedMutexHoldsTheKeyUntilUnlocked(): void
    {
        $mutex = $this->makeMutex('a');

        $this->manager->lock($mutex);

        $this->assertTrue($this->isHeld('a'));

        $this->manager->unlock($mutex);

        $this->assertFalse($this->isHeld('a'));
    }

    public function testTheSameCoroutineEntersAgainAndUnlocksOnTheLastUnlock(): void
    {
        $mutex = $this->makeMutex('a');

        $this->manager->lock($mutex);
        $this->manager->lock($mutex);

        $this->manager->unlock($mutex);

        $this->assertTrue($this->isHeld('a'));

        $this->manager->unlock($mutex);

        $this->assertFalse($this->isHeld('a'));
    }

    public function testDifferentKeysDoNotShareALock(): void
    {
        $first  = $this->makeMutex('a');
        $second = $this->makeMutex('b', waitForBlockSec: 0);

        $this->manager->lock($first);
        $this->manager->lock($second);

        $this->assertTrue($this->isHeld('a'));
        $this->assertTrue($this->isHeld('b'));

        $this->manager->unlock($second);

        $this->assertTrue($this->isHeld('a'));
        $this->assertFalse($this->isHeld('b'));

        $this->manager->unlock($first);
    }

    public function testAChildCoroutineDoesNotEnterTheParentsMutex(): void
    {
        $mutex = $this->makeMutex('a');

        $this->manager->lock($mutex);

        $results = $this->runCoroutines(
            fn(): string => $this->tryLock($this->manager, $this->makeMutex('a', waitForBlockSec: 0))
        );

        $this->assertSame(['timeout'], $results);
        $this->assertTrue($this->isHeld('a'));

        $this->manager->unlock($mutex);

        $this->assertFalse($this->isHeld('a'));
    }

    public function testAChildCoroutineDoesNotUnlockTheParentsMutex(): void
    {
        $mutex = $this->makeMutex('a');

        $this->manager->lock($mutex);

        $this->runCoroutines(
            function () use ($mutex): string {
                $this->manager->unlock($mutex);

                return 'unlocked';
            }
        );

        $this->assertTrue($this->isHeld('a'));

        $this->manager->unlock($mutex);

        $this->assertFalse($this->isHeld('a'));
    }

    public function testAMutexHeldByAChildIsNotSeenByTheParent(): void
    {
        $this->runCoroutines(
            function (): string {
                $this->manager->lock($this->makeMutex('a'));

                return 'locked';
            }
        );

        $this->assertTrue($this->isHeld('a'));

        $this->assertSame('timeout', $this->tryLock($this->manager, $this->makeMutex('a', waitForBlockSec: 0)));
    }

    public function testATimeoutNamesTheKeyAndLeavesNothingToUnlock(): void
    {
        $foreignLock = Cache::store('array')->lock('mutex:a', 10);
        $foreignLock->get();

        $mutex = $this->makeMutex('a', waitForBlockSec: 0);

        try {
            $this->manager->lock($mutex);

            $this->fail('The mutex was taken over a foreign lock');
        } catch (MutexLockTimeoutException $exception) {
            $this->assertSame('Mutex [a] was not acquired in time', $exception->getMessage());
            $this->assertInstanceOf(RuntimeException::class, $exception);
        }

        $this->manager->unlock($mutex);

        $this->assertTrue($this->isHeld('a'));

        $foreignLock->release();
    }

    public function testUnlockingAMutexThatIsNotHeldDoesNothing(): void
    {
        $this->manager->unlock($this->makeMutex('a'));

        $this->assertFalse($this->isHeld('a'));
    }

    public function testHandlersRunOnceWhileTheLockIsHeld(): void
    {
        $calls = new ArrayObject();

        $mutex = $this->makeMutex(
            key: 'a',
            afterLock: function () use ($calls): void {
                $calls[] = ['after-lock', $this->isHeld('a')];
            },
            beforeRelease: function () use ($calls): void {
                $calls[] = ['before-release', $this->isHeld('a')];
            }
        );

        $this->manager->lock($mutex);
        $this->manager->lock($mutex);
        $this->manager->unlock($mutex);
        $this->manager->unlock($mutex);

        $this->assertSame(
            [
                ['after-lock', true],
                ['before-release', true],
            ],
            $calls->getArrayCopy()
        );
    }

    public function testAFailingAfterLockHandlerReleasesTheLock(): void
    {
        $mutex = $this->makeMutex(
            key: 'a',
            afterLock: static function (): never {
                throw new RuntimeException('after-lock');
            }
        );

        try {
            $this->manager->lock($mutex);

            $this->fail('The handler exception did not reach the caller');
        } catch (RuntimeException $exception) {
            $this->assertSame('after-lock', $exception->getMessage());
        }

        $this->assertFalse($this->isHeld('a'));

        $retry = $this->makeMutex('a');

        $this->manager->lock($retry);

        $this->assertTrue($this->isHeld('a'));

        $this->manager->unlock($retry);
    }

    public function testAFailingBeforeReleaseHandlerStillReleasesTheLock(): void
    {
        $mutex = $this->makeMutex(
            key: 'a',
            beforeRelease: static function (): never {
                throw new RuntimeException('before-release');
            }
        );

        $this->manager->lock($mutex);

        try {
            $this->manager->unlock($mutex);

            $this->fail('The handler exception did not reach the caller');
        } catch (RuntimeException $exception) {
            $this->assertSame('before-release', $exception->getMessage());
        }

        $this->assertFalse($this->isHeld('a'));

        $retry = $this->makeMutex('a');

        $this->manager->lock($retry);

        $this->assertTrue($this->isHeld('a'));

        $this->manager->unlock($retry);
    }

    private function isHeld(string $key): bool
    {
        return $this->isHeldIn('array', $key);
    }
}
