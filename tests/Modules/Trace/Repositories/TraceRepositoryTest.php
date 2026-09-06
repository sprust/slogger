<?php

namespace Tests\Modules\Trace\Repositories;

use App\Modules\Trace\Repositories\Services\PeriodicTraceService;
use App\Modules\Trace\Repositories\Services\TracePipelineBuilder;
use App\Modules\Trace\Repositories\TraceRepository;
use PHPUnit\Framework\TestCase;

/**
 * Reading the profiling of one trace.
 *
 * Absence is the case worth pinning down. The receiver writes every trace document with
 * `hpr => false, pr => []`, so a trace without profiling does not lack the field — it
 * carries an empty one. Read as if it were filled, that answered the endpoint with a 500
 * where the controller turns a null into a 404.
 */
class TraceRepositoryTest extends TestCase
{
    public function testATraceWithoutProfilingReadsAsAbsent(): void
    {
        $this->assertNull($this->repository(['tid' => 'trace-1', 'hpr' => false, 'pr' => []])->findProfilingByTraceId('trace-1'));
    }

    /** A document that never carried the field at all is absent for the same reason. */
    public function testATraceWithNoProfilingFieldReadsAsAbsent(): void
    {
        $this->assertNull($this->repository(['tid' => 'trace-1'])->findProfilingByTraceId('trace-1'));
    }

    public function testATraceThatIsNotThereReadsAsAbsent(): void
    {
        $this->assertNull($this->repository(null)->findProfilingByTraceId('trace-1'));
    }

    /** A trace whose collection is unknown never reaches the document read. */
    public function testATraceWithNoCollectionReadsAsAbsent(): void
    {
        $service = $this->createMock(PeriodicTraceService::class);

        $service->method('findCollectionNameByTraceId')->willReturn(null);
        $service->expects($this->never())->method('findOne');

        $repository = new TraceRepository($this->createMock(TracePipelineBuilder::class), $service);

        $this->assertNull($repository->findProfilingByTraceId('trace-1'));
    }

    /**
     * The filled case, so the guard added for the empty one cannot start swallowing real
     * profiling.
     */
    public function testAFilledProfilingIsCarriedThroughIntact(): void
    {
        $profiling = $this->repository([
            'tid' => 'trace-1',
            'hpr' => true,
            'pr'  => [
                'mainCaller' => 'App\\Http\\Kernel::handle',
                'items'      => [
                    [
                        'raw'      => 'App\\Foo::bar',
                        'calling'  => 'App\\Http\\Kernel::handle',
                        'callable' => 'App\\Foo::bar',
                        'data'     => [
                            ['name' => 'wt', 'value' => 12.5],
                        ],
                    ],
                ],
            ],
        ])->findProfilingByTraceId('trace-1');

        $this->assertNotNull($profiling);
        $this->assertSame('App\\Http\\Kernel::handle', $profiling->mainCaller);
        $this->assertCount(1, $profiling->items);
        $this->assertSame('App\\Foo::bar', $profiling->items[0]->callable);
        $this->assertSame('wt', $profiling->items[0]->data[0]->name);
        $this->assertSame(12.5, $profiling->items[0]->data[0]->value);
    }

    /**
     * @param array<string, mixed>|null $document
     */
    private function repository(?array $document): TraceRepository
    {
        $service = $this->createMock(PeriodicTraceService::class);

        $service->method('findCollectionNameByTraceId')->willReturn('traces_2026_09_05_12_13');
        $service->method('findOne')->willReturn($document);

        return new TraceRepository($this->createMock(TracePipelineBuilder::class), $service);
    }
}
