<?php

namespace Tests\Modules\Trace\Domain\Actions\Mutations;

use App\Modules\Trace\Domain\Actions\Mutations\DeleteExpiredTraceDynamicIndexesAction;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDto;
use App\Modules\Trace\Repositories\TraceDynamicIndexRepository;
use App\Modules\Trace\Repositories\TraceRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

/**
 * One pass over the indexes whose lifetime has run out.
 *
 * The record is dropped either way; the index itself is only dropped if it was ever
 * built, because deleting one that was never created would fail on a name Mongo does
 * not have.
 */
class DeleteExpiredTraceDynamicIndexesActionTest extends TestCase
{
    public function testAnIndexThatWasBuiltIsDroppedFromMongoAndFromTheRecords(): void
    {
        $indexes = $this->createMock(TraceDynamicIndexRepository::class);
        $traces  = $this->createMock(TraceRepository::class);

        $indexes->method('find')->willReturn([$this->index(created: true)]);

        $traces->expects($this->once())
            ->method('deleteIndexByName')
            ->with('dyn_name', ['traces_2026_08']);

        $indexes->expects($this->once())
            ->method('deleteById')
            ->with('507f1f77bcf86cd799439011');

        $this->assertSame(1, new DeleteExpiredTraceDynamicIndexesAction($indexes, $traces)->handle());
    }

    public function testAnIndexThatWasNeverBuiltOnlyLosesItsRecord(): void
    {
        $indexes = $this->createMock(TraceDynamicIndexRepository::class);
        $traces  = $this->createMock(TraceRepository::class);

        $indexes->method('find')->willReturn([$this->index(created: false)]);

        $traces->expects($this->never())->method('deleteIndexByName');
        $indexes->expects($this->once())->method('deleteById');

        $this->assertSame(1, new DeleteExpiredTraceDynamicIndexesAction($indexes, $traces)->handle());
    }

    public function testAPassWithNothingExpiredReportsNoWork(): void
    {
        $indexes = $this->createMock(TraceDynamicIndexRepository::class);
        $traces  = $this->createMock(TraceRepository::class);

        $indexes->method('find')->willReturn([]);

        $indexes->expects($this->never())->method('deleteById');

        $this->assertSame(0, new DeleteExpiredTraceDynamicIndexesAction($indexes, $traces)->handle());
    }

    private function index(bool $created): TraceDynamicIndexDto
    {
        return new TraceDynamicIndexDto(
            id: '507f1f77bcf86cd799439011',
            name: 'dyn_name_coll',
            indexName: 'dyn_name',
            collectionNames: ['traces_2026_08'],
            fields: [],
            inProcess: false,
            created: $created,
            error: null,
            actualUntilAt: Carbon::now()->subHour(),
            createdAt: Carbon::now()->subDay(),
        );
    }
}
