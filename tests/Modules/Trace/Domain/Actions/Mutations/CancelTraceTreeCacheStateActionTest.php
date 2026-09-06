<?php

namespace Tests\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Mutations\CancelTraceTreeCacheStateAction;
use App\Modules\Trace\Domain\Events\TraceTreeCacheStateChangedEvent;
use App\Modules\Trace\Entities\Trace\Tree\TraceTreeCacheStateObject;
use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use App\Modules\Trace\Repositories\TraceTreeCacheStateRepository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * Cancelling a build is a state change like any other, and every other tab watching that
 * tree has to hear about it — the one that pressed the button already has the answer in
 * its response.
 */
class CancelTraceTreeCacheStateActionTest extends TestCase
{
    public function testACancelIsAnnounced(): void
    {
        $states = $this->createMock(TraceTreeCacheStateRepository::class);
        $events = $this->createMock(Dispatcher::class);

        $states->method('updateStatus')->willReturn(true);
        $states->method('findOneByRootTraceId')->willReturn($this->state());

        $events->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->callback(
                    static fn(TraceTreeCacheStateChangedEvent $event): bool => $event->state->status === TraceTreeCacheStateStatusEnum::Canceled
                )
            );

        $this->assertNotNull(new CancelTraceTreeCacheStateAction($states, $events)->handle('root'));
    }

    public function testCancellingWhatIsNotThereAnnouncesNothing(): void
    {
        $states = $this->createMock(TraceTreeCacheStateRepository::class);
        $events = $this->createMock(Dispatcher::class);

        $states->method('updateStatus')->willReturn(false);

        $events->expects($this->never())->method('dispatch');

        $this->assertNull(new CancelTraceTreeCacheStateAction($states, $events)->handle('root'));
    }

    private function state(): TraceTreeCacheStateObject
    {
        return new TraceTreeCacheStateObject(
            rootTraceId: 'root',
            version: 'v1',
            status: TraceTreeCacheStateStatusEnum::Canceled,
            count: 7,
            error: null,
            startedAt: Carbon::now(),
            finishedAt: Carbon::now(),
            createdAt: Carbon::now(),
            updatedAt: Carbon::now(),
        );
    }
}
