<?php

declare(strict_types=1);

namespace Tests\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Domain\Actions\Queries\CompareTraceGroupsAction;
use App\Modules\Trace\Domain\Actions\Queries\FindTraceGroupsAction;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonCountObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonRowObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupObject;
use App\Modules\Trace\Enums\TraceCompareByEnum;
use App\Modules\Trace\Enums\TraceGroupFieldEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterItemParameters;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
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
    private ?array $repositoryArguments = null;

    public function testGroupsPassTheFiltersOfTheTraceSearch(): void
    {
        $data = new TraceDataFilterParameters(filter: [$this->dataFilterItem()]);

        $repository = $this->repository('findGroups', [$this->group(), $this->group(), $this->group()]);

        $result = new FindTraceGroupsAction($repository)->handle(
            new TraceFindGroupsParameters(
                loggingPeriod: $this->period(),
                groupBy: [TraceGroupFieldEnum::Type],
                limit: 2,
                serviceIds: [2],
                types: ['request'],
                tags: ['api'],
                statuses: ['failed'],
                durationFrom: 0.5,
                data: $data
            )
        );

        $this->assertCount(2, $result->items);
        $this->assertTrue($result->truncated);
        $this->assertSame('2026-09-28 10:00:00', $this->repositoryArguments['loggedAtFrom']?->toDateTimeString());
        $this->assertSame('2026-09-28 10:59:59', $this->repositoryArguments['loggedAtTo']?->toDateTimeString());
        $this->assertSame(
            [
                'groupBy'      => [TraceGroupFieldEnum::Type],
                'limit'        => 3,
                'serviceIds'   => [2],
                'types'        => ['request'],
                'tags'         => ['api'],
                'statuses'     => ['failed'],
                'durationFrom' => 0.5,
                'durationTo'   => null,
                'data'         => $data,
            ],
            array_intersect_key(
                $this->repositoryArguments ?? [],
                array_flip(
                    ['groupBy', 'limit', 'serviceIds', 'types', 'tags', 'statuses', 'durationFrom', 'durationTo', 'data']
                )
            )
        );
    }

    public function testGroupsWithoutDataAreNotFilteredByData(): void
    {
        $repository = $this->repository('findGroups', [$this->group()]);

        $result = new FindTraceGroupsAction($repository)->handle(
            new TraceFindGroupsParameters(
                loggingPeriod: $this->period(),
                groupBy: [TraceGroupFieldEnum::Type],
                limit: 2
            )
        );

        $this->assertCount(1, $result->items);
        $this->assertFalse($result->truncated);
        $this->assertArrayHasKey('data', $this->repositoryArguments ?? []);
        $this->assertNull($this->repositoryArguments['data']);
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
        $this->assertSame([], $this->repositoryArguments['statuses'] ?? null);
    }

    public function testGroupBLimitsTheStatuses(): void
    {
        $this->compare([], groupBStatuses: ['success']);

        $this->assertSame(['failed', 'success'], $this->repositoryArguments['statuses'] ?? null);
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
        $repository = $this->repository('compareGroups', $counts);

        return new CompareTraceGroupsAction($repository)->handle(
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

    /**
     * A repository mock that remembers the arguments of the method by their names.
     *
     * @param mixed[] $result
     */
    private function repository(string $method, array $result): TraceGroupsRepository
    {
        $repository = $this->createMock(TraceGroupsRepository::class);
        $names      = array_map(
            static fn(ReflectionParameter $parameter) => $parameter->getName(),
            new ReflectionMethod(TraceGroupsRepository::class, $method)->getParameters()
        );

        $repository->expects($this->once())
            ->method($method)
            ->willReturnCallback(function (...$arguments) use ($names, $result): array {
                $this->repositoryArguments = array_combine(array_slice($names, 0, count($arguments)), $arguments);

                return $result;
            });

        return $repository;
    }

    private function dataFilterItem(): TraceDataFilterItemParameters
    {
        return new TraceDataFilterItemParameters(
            field: 'dt.response.status',
            null: null,
            exists: true,
            numeric: null,
            string: null,
            boolean: null
        );
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
