<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Actions\Queries;

use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonCountObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonObject;
use App\Modules\Trace\Entities\Trace\Groups\TraceGroupComparisonRowObject;
use App\Modules\Trace\Parameters\TraceCompareGroupsParameters;
use App\Modules\Trace\Repositories\TraceGroupsRepository;
use Illuminate\Support\Carbon;
use App\Services\Clickhouse\ClickhouseQueryException;

readonly class CompareTraceGroupsAction
{
    public function __construct(
        private TraceGroupsRepository $traceGroupsRepository
    ) {
    }

    /**
     * @throws ClickhouseQueryException
     */
    public function handle(TraceCompareGroupsParameters $parameters): TraceGroupComparisonObject
    {
        $loggedAtFrom = $parameters->loggingPeriod->from ?? Carbon::now();
        $loggedAtTo   = $parameters->loggingPeriod->to ?? Carbon::now();

        $statuses = count($parameters->groupBStatuses) > 0
            ? array_values(array_unique([...$parameters->groupAStatuses, ...$parameters->groupBStatuses]))
            : [];

        $counts = $this->traceGroupsRepository->compareGroups(
            loggedAtFrom: $loggedAtFrom,
            loggedAtTo: $loggedAtTo,
            groupAStatuses: $parameters->groupAStatuses,
            by: $parameters->by,
            dataKey: $parameters->dataKey,
            serviceIds: $parameters->serviceIds,
            types: $parameters->types,
            statuses: $statuses
        );

        $groupATotal = 0;
        $groupBTotal = 0;

        /** @var array<string, TraceGroupComparisonCountObject[]> $byValue */
        $byValue = [];

        foreach ($counts as $count) {
            if ($count->inGroupA) {
                $groupATotal += $count->count;
            } else {
                $groupBTotal += $count->count;
            }

            $byValue[$this->valueKey($count->value)][] = $count;
        }

        $rows = [];

        foreach ($byValue as $valueCounts) {
            $groupACount = 0;
            $groupBCount = 0;

            foreach ($valueCounts as $count) {
                if ($count->inGroupA) {
                    $groupACount += $count->count;
                } else {
                    $groupBCount += $count->count;
                }
            }

            $rows[] = new TraceGroupComparisonRowObject(
                value: $valueCounts[0]->value,
                groupACount: $groupACount,
                groupAShare: $this->share($groupACount, $groupATotal),
                groupBCount: $groupBCount,
                groupBShare: $this->share($groupBCount, $groupBTotal)
            );
        }

        usort(
            $rows,
            static fn(TraceGroupComparisonRowObject $a, TraceGroupComparisonRowObject $b): int => [
                $b->groupAShare - $b->groupBShare,
                $b->groupACount,
            ] <=> [
                $a->groupAShare - $a->groupBShare,
                $a->groupACount,
            ]
        );

        return new TraceGroupComparisonObject(
            groupATotal: $groupATotal,
            groupBTotal: $groupBTotal,
            rows: array_slice($rows, 0, $parameters->limit),
            truncated: count($rows) > $parameters->limit
        );
    }

    private function share(int $count, int $total): float
    {
        return $total === 0 ? 0.0 : round($count / $total, 4);
    }

    private function valueKey(string|int|float|bool|null $value): string
    {
        return get_debug_type($value) . ':' . var_export($value, true);
    }
}
