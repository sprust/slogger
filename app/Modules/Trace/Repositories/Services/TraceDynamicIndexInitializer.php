<?php

namespace App\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Repositories\Dto\Data\TraceDataFilterDto;
use App\Modules\Trace\Repositories\Dto\TraceDynamicIndexFieldDto;
use App\Modules\Trace\Repositories\Dto\TraceSortDto;
use App\Modules\Trace\Repositories\Interfaces\TraceDynamicIndexRepositoryInterface;
use Illuminate\Support\Carbon;

readonly class TraceDynamicIndexInitializer
{
    private int $timeLifeIndexInMinutes;

    public function __construct(
        private TraceDynamicIndexRepositoryInterface $traceDynamicIndexRepository
    ) {
        $this->timeLifeIndexInMinutes = 15;
    }

    /**
     * @param int[]|null          $serviceIds
     * @param string[]            $types
     * @param string[]            $tags
     * @param string[]            $statuses
     * @param TraceSortDto[]|null $sort
     *
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexInProcessException
     */
    public function init(
        ?array $serviceIds = null,
        ?array $traceIds = null,
        ?Carbon $loggedAtFrom = null,
        ?Carbon $loggedAtTo = null,
        array $types = [],
        array $tags = [],
        array $statuses = [],
        ?float $durationFrom = null,
        ?float $durationTo = null,
        ?float $memoryFrom = null,
        ?float $memoryTo = null,
        ?float $cpuFrom = null,
        ?float $cpuTo = null,
        ?TraceDataFilterDto $data = null,
        ?bool $hasProfiling = null,
        ?array $sort = null,
    ): void {
        $fields = [];

        if (!empty($serviceIds)) {
            $fields[] = new TraceDynamicIndexFieldDto('serviceId');
        }

        if (!empty($traceIds)) {
            $fields[] = new TraceDynamicIndexFieldDto('traceId');
        }

        if (!empty($loggedAtFrom) || !empty($loggedAtTo)) {
            $fields[] = new TraceDynamicIndexFieldDto('loggedAt');
        }

        if (!empty($types)) {
            $fields[] = new TraceDynamicIndexFieldDto('type');
        }

        if (!empty($tags)) {
            $fields[] = new TraceDynamicIndexFieldDto('tags');
        }
        if (!empty($statuses)) {
            $fields[] = new TraceDynamicIndexFieldDto('status');
        }

        if (!is_null($durationFrom) || !is_null($durationTo)) {
            $fields[] = new TraceDynamicIndexFieldDto('duration');
        }

        if (!is_null($memoryFrom) || !is_null($memoryTo)) {
            $fields[] = new TraceDynamicIndexFieldDto('memory');
        }

        if (!is_null($cpuFrom) || !is_null($cpuTo)) {
            $fields[] = new TraceDynamicIndexFieldDto('cpu');
        }

        if (!is_null($hasProfiling)) {
            $fields[] = new TraceDynamicIndexFieldDto('hasProfiling');
        }

        foreach ($data->filter ?? [] as $dataFilterItem) {
            $fields[] = new TraceDynamicIndexFieldDto(
                fieldName: $dataFilterItem->field
            );
        }

        foreach ($sort ?? [] as $sortItem) {
            $fields[] = new TraceDynamicIndexFieldDto($sortItem->field);
        }

        if (empty($fields)) {
            return;
        }

        $indexDto = $this->traceDynamicIndexRepository->findOneOrCreate(
            fields: $fields,
            actualUntilAt: now()->addMinutes($this->timeLifeIndexInMinutes)
        );

        if (!$indexDto) {
            throw new TraceDynamicIndexNotInitException();
        }

        if ($indexDto->inProcess) {
            throw new TraceDynamicIndexInProcessException();
        }
    }
}
