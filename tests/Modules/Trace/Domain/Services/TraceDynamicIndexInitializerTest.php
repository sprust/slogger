<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Domain\Services;

use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexParallelArraysException;
use App\Modules\Trace\Domain\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Enums\TraceDataFilterCompStringTypeEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterItemParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterStringParameters;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDto;
use App\Modules\Trace\Repositories\TraceDynamicIndexRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class TraceDynamicIndexInitializerTest extends TestCase
{
    public function testTagsWithADataFieldAreRefusedBeforeAnythingIsWritten(): void
    {
        $repository = $this->createMock(TraceDynamicIndexRepository::class);

        $repository->expects($this->never())->method('findOneOrCreate');

        $this->expectException(TraceDynamicIndexParallelArraysException::class);

        new TraceDynamicIndexInitializer($repository)->init(
            tags: ['api'],
            data: $this->dataFilter()
        );
    }

    public function testTagsOnTheirOwnAskForAnIndex(): void
    {
        $repository = $this->createMock(TraceDynamicIndexRepository::class);

        $repository->expects($this->once())
            ->method('findOneOrCreate')
            ->willReturn($this->index());

        new TraceDynamicIndexInitializer($repository)->init(tags: ['api']);
    }

    public function testADataFieldOnItsOwnAsksForAnIndex(): void
    {
        $repository = $this->createMock(TraceDynamicIndexRepository::class);

        $repository->expects($this->once())
            ->method('findOneOrCreate')
            ->willReturn($this->index());

        new TraceDynamicIndexInitializer($repository)->init(data: $this->dataFilter());
    }

    public function testTagsBesideAnEmptyDataFilterAreNotARefusal(): void
    {
        $repository = $this->createMock(TraceDynamicIndexRepository::class);

        $repository->expects($this->once())
            ->method('findOneOrCreate')
            ->willReturn($this->index());

        new TraceDynamicIndexInitializer($repository)->init(
            tags: ['api'],
            data: new TraceDataFilterParameters()
        );
    }

    private function dataFilter(): TraceDataFilterParameters
    {
        return new TraceDataFilterParameters(
            filter: [
                new TraceDataFilterItemParameters(
                    field: 'dt.job',
                    null: null,
                    exists: null,
                    numeric: null,
                    string: new TraceDataFilterStringParameters(
                        value: 'run',
                        comp: TraceDataFilterCompStringTypeEnum::Eq
                    ),
                    boolean: null
                ),
            ]
        );
    }

    private function index(): TraceDynamicIndexDto
    {
        $now = Carbon::parse('2026-09-19 12:00:00');

        return new TraceDynamicIndexDto(
            id: '68be1f000000000000000001',
            name: 'dyn_tgs.nm_traces_2026_09_19_12_13',
            indexName: 'dyn_tgs.nm',
            collectionNames: ['traces_2026_09_19_12_13'],
            fields: [],
            inProcess: false,
            created: true,
            error: null,
            actualUntilAt: $now->clone()->addHours(12),
            createdAt: $now
        );
    }
}
