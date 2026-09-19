<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Mutations\DeleteTraceTreeCacheAction;
use App\Modules\Trace\Domain\Actions\Queries\IsShouldContinueBuildTraceTreeCacheAction;
use App\Modules\Trace\Domain\Events\TraceTreeCacheBuildRequestedEvent;
use App\Modules\Trace\Domain\Events\TraceTreeCacheDeleteRequestedEvent;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use Illuminate\Contracts\Events\Dispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Wiping a tree is a chain of chunks rather than one deleteMany.
 *
 * Four million documents in one command outlive the driver's deadline, and the error
 * that came back — `mongodb: dlm: operation timed out` — failed the build before it had
 * walked a single node.
 */
class DeleteTraceTreeCacheActionTest extends TestCase
{
    public function testAFullChunkAsksForTheNextOne(): void
    {
        $events = $this->createMock(Dispatcher::class);

        $events->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(
                    static fn(TraceTreeCacheDeleteRequestedEvent $event): bool => $event->rootTraceId === 'root'
                        && $event->buildVersion === 'v1'
                )
            );

        $this->action($events, deleted: 10000)->handle('root', 'v1');
    }

    public function testTheLastChunkStartsTheBuildItWasClearingFor(): void
    {
        $events = $this->createMock(Dispatcher::class);

        $events->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(
                    static fn(TraceTreeCacheBuildRequestedEvent $event): bool => $event->rootTraceId === 'root'
                        && $event->version === 'v1'
                        && $event->depth === 0
                        && $event->afterId === null
                )
            );

        $this->action($events, deleted: 17)->handle('root', 'v1');
    }

    public function testADeleteOfItsOwnStartsNothing(): void
    {
        $events = $this->createMock(Dispatcher::class);

        $events->expects($this->never())->method('dispatch');

        $this->action($events, deleted: 0)->handle('root');
    }

    /**
     * Cancel is answered before anything is deleted: the state on record is no longer
     * this build's, so neither the wiping nor the build that follows it is wanted.
     */
    public function testACanceledBuildDeletesNothing(): void
    {
        $events = $this->createMock(Dispatcher::class);
        $events->expects($this->never())->method('dispatch');

        $cache = $this->createMock(TraceTreeCacheRepository::class);
        $cache->expects($this->never())->method('deleteChunk');

        $shouldContinue = $this->createMock(IsShouldContinueBuildTraceTreeCacheAction::class);
        $shouldContinue->method('handle')->willReturn(false);

        new DeleteTraceTreeCacheAction($cache, $shouldContinue, $events)->handle('root', 'v1');
    }

    /**
     * @param MockObject&Dispatcher $events
     */
    private function action(MockObject $events, int $deleted): DeleteTraceTreeCacheAction
    {
        $cache = $this->createMock(TraceTreeCacheRepository::class);
        $cache->method('deleteChunk')->willReturn($deleted);

        $shouldContinue = $this->createMock(IsShouldContinueBuildTraceTreeCacheAction::class);
        $shouldContinue->method('handle')->willReturn(true);

        return new DeleteTraceTreeCacheAction($cache, $shouldContinue, $events);
    }
}
