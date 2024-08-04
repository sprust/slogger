<?php

namespace App\Modules\Trace\Repositories\Services;

use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Enums\TraceTimestampEnum;
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
        $this->timeLifeIndexInMinutes = 60 * 24 * 30; // 2 days
    }

    /**
     * @param int[]|null          $serviceIds
     * @param string[]            $types
     * @param string[]            $tags
     * @param string[]            $statuses
     * @param TraceSortDto[]|null $sort
     *
     * TODO: violation of layers
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexInProcessException
     */
    public function init(
        ?array $serviceIds = null,
        ?TraceTimestampEnum $timestampStep = null,
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
        $indexFields = [];

        if (!empty($serviceIds)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('serviceId');
        }

        if (!is_null($timestampStep)) {
            $indexFields[] = new TraceDynamicIndexFieldDto("timestamps.$timestampStep->value");
        }

        if (!empty($traceIds)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('traceId');
        }

        if (!empty($loggedAtFrom) || !empty($loggedAtTo)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('loggedAt');
        }

        if (!empty($types)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('type');
        }

        // TODO: the index for arrays not working like that
        if (!empty($tags)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('tags');
        }

        if (!empty($statuses)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('status');
        }

        if (!is_null($durationFrom) || !is_null($durationTo)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('duration');
        }

        if (!is_null($memoryFrom) || !is_null($memoryTo)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('memory');
        }

        if (!is_null($cpuFrom) || !is_null($cpuTo)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('cpu');
        }

        if (!is_null($hasProfiling)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('hasProfiling');
        }

        foreach ($data->filter ?? [] as $dataFilterItem) {
            $indexFields[] = new TraceDynamicIndexFieldDto(
                fieldName: $dataFilterItem->field
            );
        }

        foreach ($sort ?? [] as $sortItem) {
            $indexFields[] = new TraceDynamicIndexFieldDto($sortItem->field);
        }

        if (empty($indexFields)) {
            return;
        }

        $indexFields = collect($indexFields)
            ->unique(
                fn(TraceDynamicIndexFieldDto $dto) => $dto->fieldName
            )
            ->all();

        $indexDto = $this->traceDynamicIndexRepository->findOneOrCreate(
            fields: $indexFields,
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
