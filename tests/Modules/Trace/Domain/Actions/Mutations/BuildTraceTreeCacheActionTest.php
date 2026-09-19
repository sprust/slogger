<?php

namespace Tests\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Mutations\BuildTraceTreeCacheAction;
use App\Modules\Trace\Domain\Actions\Queries\IsShouldContinueBuildTraceTreeCacheAction;
use App\Modules\Trace\Domain\Events\TraceTreeCacheStateChangedEvent;
use App\Modules\Trace\Domain\Services\TraceTreeCacheBuilderService;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheSliceObject;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Domain\Events\TraceTreeCacheBuildRequestedEvent;
use App\Modules\Trace\Repositories\TraceTreeCacheRepository;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * The end of one tree build, and who gets told about it.
 *
 * The panel used to find out by asking again every second, so what matters here is that
 * every way a build can stop announces itself — and that a build which stopped being this
 * one's business announces nothing.
 */
class BuildTraceTreeCacheActionTest extends TestCase
{
    public function testAFinishedBuildIsAnnounced(): void
    {
        $states = $this->createMock(TraceTreeCacheStateRepository::class);
        $events = $this->createMock(Dispatcher::class);

        $states->method('markFinished')->willReturn(true);
        $states->method('findOneByRootTraceId')
            ->willReturn($this->state(TraceTreeCacheStateStatusEnum::Finished));

        $events->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(
                    static fn(TraceTreeCacheStateChangedEvent $event): bool => $event->state->status === TraceTreeCacheStateStatusEnum::Finished
                )
            );

        $this->action($states, $events, builderResult: $this->finishedSlice())->handle('root', 'v1');
    }

    /**
     * A slice that is not the last one asks for the next slice and announces nothing:
     * the tree has not stopped moving, it has merely changed hands.
     */
    public function testASliceAsksForTheNextOne(): void
    {
        $states = $this->createMock(TraceTreeCacheStateRepository::class);
        $events = $this->createMock(Dispatcher::class);

        $states->expects($this->never())->method('markFinished');

        $events->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(
                    static fn(TraceTreeCacheBuildRequestedEvent $event): bool => $event->depth === 2
                        && $event->afterId === 'cursor-1'
                )
            );

        $this->action(
            $states,
            $events,
            builderResult: new TraceTreeCacheSliceObject(
                stopped: false,
                finished: false,
                nextDepth: 2,
                nextAfterId: 'cursor-1'
            )
        )->handle('root', 'v1', 1, null);
    }

    public function testACanceledSliceAsksForNothing(): void
    {
        $states = $this->createMock(TraceTreeCacheStateRepository::class);
        $events = $this->createMock(Dispatcher::class);

        $states->expects($this->never())->method('markFinished');

        $events->expects($this->never())->method('dispatch');

        $this->action(
            $states,
            $events,
            builderResult: new TraceTreeCacheSliceObject(
                stopped: true,
                finished: false,
                nextDepth: null,
                nextAfterId: null
            )
        )->handle('root', 'v1');
    }

    public function testAFailedBuildIsAnnouncedAsFailed(): void
    {
        $states = $this->createMock(TraceTreeCacheStateRepository::class);
        $events = $this->createMock(Dispatcher::class);

        $states->expects($this->once())->method('markFailed')->willReturn(true);
        $states->method('findOneByRootTraceId')
            ->willReturn($this->state(TraceTreeCacheStateStatusEnum::Failed));

        $events->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(
                    static fn(TraceTreeCacheStateChangedEvent $event): bool => $event->state->status === TraceTreeCacheStateStatusEnum::Failed
                )
            );

        $this->action($states, $events, builderResult: new RuntimeException('mongo said no'))
            ->handle('root', 'v1');
    }

    public function testABuildSupersededWhileItRanAnnouncesNothing(): void
    {
        $states = $this->createMock(TraceTreeCacheStateRepository::class);
        $events = $this->createMock(Dispatcher::class);

        // The mark matched nothing: this version is no longer the one on record, so the
        // state now stored belongs to somebody else's build.
        $states->method('markFinished')->willReturn(false);
        $states->expects($this->never())->method('findOneByRootTraceId');

        $events->expects($this->never())->method('dispatch');

        $this->action($states, $events, builderResult: $this->finishedSlice())->handle('root', 'v1');
    }

    public function testAnIncompleteBuildAnnouncesNothing(): void
    {
        $states = $this->createMock(TraceTreeCacheStateRepository::class);
        $events = $this->createMock(Dispatcher::class);

        // The builder gave up half way — cancelled, or superseded. Nothing was marked.
        $states->expects($this->never())->method('markFinished');

        $events->expects($this->never())->method('dispatch');

        $this->action(
            $states,
            $events,
            builderResult: new TraceTreeCacheSliceObject(
                stopped: true,
                finished: false,
                nextDepth: null,
                nextAfterId: null
            )
        )->handle('root', 'v1');
    }

    public function testAnnouncingIsNotAllowedToTurnAFinishedBuildIntoAFailedOne(): void
    {
        $states = $this->createMock(TraceTreeCacheStateRepository::class);
        $events = $this->createMock(Dispatcher::class);

        $states->method('markFinished')->willReturn(true);
        $states->method('findOneByRootTraceId')
            ->willReturn($this->state(TraceTreeCacheStateStatusEnum::Finished));

        // A bus that is down. ShouldRescue keeps this from happening at all in practice,
        // but markFailed() filters by version alone: were the announcement inside the
        // catch, a throw here would overwrite the state of a tree that is fully built.
        $events->method('dispatch')->willThrowException(new RuntimeException('bus is down'));

        $states->expects($this->never())->method('markFailed');

        $this->expectException(RuntimeException::class);

        $this->action($states, $events, builderResult: $this->finishedSlice())->handle('root', 'v1');
    }

    private function finishedSlice(): TraceTreeCacheSliceObject
    {
        return new TraceTreeCacheSliceObject(
            stopped: false,
            finished: true,
            nextDepth: null,
            nextAfterId: null
        );
    }

    /**
     * @param MockObject&TraceTreeCacheStateRepository   $states
     * @param MockObject&Dispatcher                      $events
     * @param TraceTreeCacheSliceObject|RuntimeException $builderResult what the slice does
     */
    private function action(
        MockObject $states,
        MockObject $events,
        TraceTreeCacheSliceObject|RuntimeException $builderResult
    ): BuildTraceTreeCacheAction {
        $builder = $this->createMock(TraceTreeCacheBuilderService::class);

        if ($builderResult instanceof RuntimeException) {
            $builder->method('handleSlice')->willThrowException($builderResult);
        } else {
            $builder->method('handleSlice')->willReturn($builderResult);
        }

        $cache = $this->createMock(TraceTreeCacheRepository::class);
        $cache->method('findCount')->willReturn(42);

        $shouldContinue = $this->createMock(IsShouldContinueBuildTraceTreeCacheAction::class);
        $shouldContinue->method('handle')->willReturn(true);

        return new BuildTraceTreeCacheAction($builder, $cache, $states, $shouldContinue, $events);
    }

    private function state(TraceTreeCacheStateStatusEnum $status): TraceTreeCacheStateObject
    {
        return new TraceTreeCacheStateObject(
            rootTraceId: 'root',
            version: 'v1',
            status: $status,
            count: 42,
            error: null,
            startedAt: Carbon::now(),
            finishedAt: Carbon::now(),
            createdAt: Carbon::now(),
            updatedAt: Carbon::now(),
        );
    }
}
