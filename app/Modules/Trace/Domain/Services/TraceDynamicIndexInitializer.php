<?php

namespace App\Modules\Trace\Domain\Services;

use App\Modules\Trace\Contracts\Repositories\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Contracts\Repositories\TraceRepositoryInterface;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Parameters\TraceSortParameters;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexFieldDto;
use Illuminate\Support\Carbon;

readonly class TraceDynamicIndexInitializer
{
    private int $timeLifeIndexInMinutes;

    public function __construct(
        private TraceDynamicIndexRepositoryInterface $traceDynamicIndexRepository,
        private TraceRepositoryInterface $traceRepository,
    ) {
        $this->timeLifeIndexInMinutes = 60 * 24 * 30; // 30 days
    }

    /**
     * WARNING: mongodb not support the index parallel arrays
     *
     * @see https://www.mongodb.com/docs/manual/core/indexes/index-types/index-multikey/#compound-multikey-indexes
     *
     * @param int[]|null                 $serviceIds
     * @param string[]                   $types
     * @param string[]                   $tags
     * @param string[]                   $statuses
     * @param TraceSortParameters[]|null $sort
     *
     * @throws TraceDynamicIndexNotInitException
     * @throws TraceDynamicIndexInProcessException
     * @throws TraceDynamicIndexErrorException
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
        ?TraceDataFilterParameters $data = null,
        ?bool $hasProfiling = null,
        ?bool $cleared = null,
        ?array $sort = null,
    ): void {
        $indexFields = [];

        if (!empty($serviceIds)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('sid');
        }

        if (!is_null($timestampStep)) {
            $indexFields[] = new TraceDynamicIndexFieldDto("tss.$timestampStep->value");
        }

        if (!empty($traceIds)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('tid');
        }

        if (!empty($loggedAtFrom) || !empty($loggedAtTo)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('lat');
        }

        if (!empty($types)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('tp');
        }

        if (!empty($tags)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('tgs.nm');
        }

        if (!empty($statuses)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('st');
        }

        if (!is_null($durationFrom) || !is_null($durationTo)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('dur');
        }

        if (!is_null($memoryFrom) || !is_null($memoryTo)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('mem');
        }

        if (!is_null($cpuFrom) || !is_null($cpuTo)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('cpu');
        }

        if (!is_null($hasProfiling)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('hpr');
        }

        if (!is_null($cleared)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('cl');
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
            $progress = $this->traceRepository->getIndexProgressInfo(
                name: $indexDto->name
            );

            throw (new TraceDynamicIndexInProcessException())->setProgress($progress?->progress);
        }

        if ($indexDto->error) {
            throw new TraceDynamicIndexErrorException(
                $indexDto->error
            );
        }
    }
}
