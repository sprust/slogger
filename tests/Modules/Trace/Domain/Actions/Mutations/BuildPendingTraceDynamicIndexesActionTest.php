<?php

namespace Tests\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Mutations\BuildPendingTraceDynamicIndexesAction;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDto;
use App\Modules\Trace\Repositories\TraceDynamicIndexRepository;
use App\Modules\Trace\Repositories\TraceRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * One pass over the indexes waiting to be built.
 *
 * What matters here is that the state of every index the pass touched is written back,
 * whichever way the build went: an index left with inProcess true is picked up again by
 * every later pass, for ever.
 */
class BuildPendingTraceDynamicIndexesActionTest extends TestCase
{
    public function testABuiltIndexIsMarkedCreated(): void
    {
        $indexes = $this->createMock(TraceDynamicIndexRepository::class);
        $traces  = $this->createMock(TraceRepository::class);

        $indexes->method('find')->willReturn([$this->index()]);
        $traces->method('createIndex')->willReturn(true);

        $indexes->expects($this->once())
            ->method('updateByName')
            ->with('dyn_name_coll', false, true, null);

        $this->assertSame(1, new BuildPendingTraceDynamicIndexesAction($indexes, $traces)->handle());
    }

    public function testAFailedBuildIsRecordedInsteadOfEscaping(): void
    {
        $indexes = $this->createMock(TraceDynamicIndexRepository::class);
        $traces  = $this->createMock(TraceRepository::class);

        $exception = new RuntimeException('mongo said no');

        $indexes->method('find')->willReturn([$this->index()]);
        $traces->method('createIndex')->willThrowException($exception);

        // Left to propagate, one unbuildable index would stop every index queued behind
        // it from ever being tried.
        $indexes->expects($this->once())
            ->method('updateByName')
            ->with('dyn_name_coll', false, false, $exception);

        $this->assertSame(1, new BuildPendingTraceDynamicIndexesAction($indexes, $traces)->handle());
    }

    public function testAPassWithNothingPendingReportsNoWork(): void
    {
        $indexes = $this->createMock(TraceDynamicIndexRepository::class);
        $traces  = $this->createMock(TraceRepository::class);

        $indexes->method('find')->willReturn([]);

        $indexes->expects($this->never())->method('updateByName');

        // The pool reads this as "idle" and waits before asking again, instead of
        // spinning on an empty collection.
        $this->assertSame(0, new BuildPendingTraceDynamicIndexesAction($indexes, $traces)->handle());
    }

    private function index(): TraceDynamicIndexDto
    {
        return new TraceDynamicIndexDto(
            id: '507f1f77bcf86cd799439011',
            name: 'dyn_name_coll',
            indexName: 'dyn_name',
            collectionNames: ['traces_2026_08'],
            fields: [],
            inProcess: true,
            created: false,
            error: null,
            actualUntilAt: Carbon::now()->addHour(),
            createdAt: Carbon::now(),
        );
    }
}
