<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Actions\Queries\CompareTraceGroupsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceGroupsAction;
use App\Modules\Trace\Domain\Services\TraceDynamicIndexInitializer;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonCountObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonRowObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupObject;
use App\Modules\Trace\Enums\TraceCompareByEnum;
use App\Modules\Trace\Enums\TraceGroupFieldEnum;
use App\Modules\Trace\Parameters\PeriodParameters;
use App\Modules\Trace\Parameters\TraceCompareGroupsParameters;
use App\Modules\Trace\Parameters\TraceFindGroupsParameters;
use App\Modules\Trace\Repositories\TraceGroupsRepository;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionParameter;

class TraceGroupsActionsTest extends TestCase
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $initArguments = null;

    public function testGroupsUseTheIndexOfTheTraceSearch(): void
    {
        $repository = $this->createMock(TraceGroupsRepository::class);
        $repository->expects($this->once())
            ->method('findGroups')
            ->with($this->anything(), $this->anything(), [TraceGroupFieldEnum::Type], 3)
            ->willReturn([$this->group(), $this->group(), $this->group()]);

        $result = new FindTraceGroupsAction($this->initializer(), $repository)->handle(
            new TraceFindGroupsParameters(
                loggingPeriod: $this->period(),
                groupBy: [TraceGroupFieldEnum::Type],
                limit: 2,
                serviceIds: [2],
                types: ['request'],
                tags: ['api'],
                statuses: ['failed'],
                durationFrom: 0.5
            )
        );

        $this->assertCount(2, $result->items);
        $this->assertTrue($result->truncated);
        $this->assertSame(
            [
                'serviceIds'   => [2],
                'types'        => ['request'],
                'tags'         => ['api'],
                'statuses'     => ['failed'],
                'durationFrom' => 0.5,
                'needLoggedAt' => true,
            ],
            array_intersect_key(
                $this->initArguments ?? [],
                array_flip(['serviceIds', 'types', 'tags', 'statuses', 'durationFrom', 'needLoggedAt'])
            )
        );
    }

    public function testComparisonSharesAndOrder(): void
    {
        $result = $this->compare(
            [
                new TraceGroupComparisonCountObject(value: 'request', inGroupA: true, count: 8),
                new TraceGroupComparisonCountObject(value: 'request', inGroupA: false, count: 10),
                new TraceGroupComparisonCountObject(value: 'job', inGroupA: true, count: 2),
                new TraceGroupComparisonCountObject(value: 'job', inGroupA: false, count: 90),
                new TraceGroupComparisonCountObject(value: null, inGroupA: false, count: 0),
            ],
            groupBStatuses: []
        );

        $this->assertSame(10, $result->groupATotal);
        $this->assertSame(100, $result->groupBTotal);
        $this->assertSame(
            [['request', 8, 0.8, 10, 0.1], [null, 0, 0.0, 0, 0.0], ['job', 2, 0.2, 90, 0.9]],
            array_map(
                static fn(TraceGroupComparisonRowObject $row) => [
                    $row->value,
                    $row->groupACount,
                    $row->groupAShare,
                    $row->groupBCount,
                    $row->groupBShare,
                ],
                $result->rows
            )
        );
        $this->assertFalse($result->truncated);
        $this->assertSame([], $this->initArguments['statuses'] ?? null);
    }

    public function testGroupBLimitsTheStatuses(): void
    {
        $this->compare([], groupBStatuses: ['success']);

        $this->assertSame(['failed', 'success'], $this->initArguments['statuses'] ?? null);
    }

    public function testComparisonRowsAreLimited(): void
    {
        $counts = array_map(
            static fn(int $i) => new TraceGroupComparisonCountObject(value: "v$i", inGroupA: true, count: $i),
            range(1, 5)
        );

        $result = $this->compare($counts, groupBStatuses: [], limit: 3);

        $this->assertCount(3, $result->rows);
        $this->assertTrue($result->truncated);
        $this->assertSame('v5', $result->rows[0]->value);
    }

    /**
     * @param TraceGroupComparisonCountObject[] $counts
     * @param string[]                          $groupBStatuses
     */
    private function compare(array $counts, array $groupBStatuses, int $limit = 30): TraceGroupComparisonObject
    {
        $repository = $this->createMock(TraceGroupsRepository::class);
        $repository->method('compareGroups')->willReturn($counts);

        return new CompareTraceGroupsAction($this->initializer(), $repository)->handle(
            new TraceCompareGroupsParameters(
                loggingPeriod: $this->period(),
                groupAStatuses: ['failed'],
                groupBStatuses: $groupBStatuses,
                by: TraceCompareByEnum::Type,
                dataKey: null,
                limit: $limit
            )
        );
    }

    private function initializer(): TraceDynamicIndexInitializer
    {
        $initializer = $this->createMock(TraceDynamicIndexInitializer::class);
        $names       = array_map(
            static fn(ReflectionParameter $parameter) => $parameter->getName(),
            new ReflectionMethod(TraceDynamicIndexInitializer::class, 'init')->getParameters()
        );

        $initializer->method('init')->willReturnCallback(function (...$arguments) use ($names): void {
            $this->initArguments = array_combine(array_slice($names, 0, count($arguments)), $arguments);
        });

        return $initializer;
    }

    private function period(): PeriodParameters
    {
        return new PeriodParameters(
            from: Carbon::parse('2026-09-28 10:00:00', 'UTC'),
            to: Carbon::parse('2026-09-28 10:59:59', 'UTC')
        );
    }

    private function group(): TraceGroupObject
    {
        return new TraceGroupObject(
            serviceId: null,
            type: 'request',
            status: null,
            startedAt: null,
            count: 1,
            durationAvg: null,
            durationP95: null,
            durationMax: null,
            exampleTraceId: null
        );
    }
}
