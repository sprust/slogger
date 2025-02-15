<?php

declare(strict_types=1);

namespace App\Modules\Trace\Domain\Services;

use App\Modules\Cleaner\Contracts\Actions\FindMaxDaysSettingActionInterface;
use App\Modules\Trace\Contracts\Repositories\TraceDynamicIndexRepositoryInterface;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexErrorException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexInProcessException;
use App\Modules\Trace\Domain\Exceptions\TraceDynamicIndexNotInitException;
use App\Modules\Trace\Enums\TraceTimestampEnum;
use App\Modules\Trace\Parameters\Data\TraceDataFilterParameters;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexDataDto;
use App\Modules\Trace\Repositories\Dto\DynamicIndex\TraceDynamicIndexFieldDto;
use Illuminate\Support\Carbon;

readonly class TraceDynamicIndexInitializer
{
    private int $shortTermTimeLifeIndexInDays;

    public function __construct(
        private TraceDynamicIndexRepositoryInterface $traceDynamicIndexRepository,
        private FindMaxDaysSettingActionInterface $findMaxDaysSettingAction,
    ) {
        $this->shortTermTimeLifeIndexInDays = 5;
    }

    /**
     * WARNING: mongodb not support the index parallel arrays
     *
     * @see https://www.mongodb.com/docs/manual/core/indexes/index-types/index-multikey/#compound-multikey-indexes
     *
     * @param int[]|null $serviceIds
     * @param string[]   $traceIds
     * @param string[]   $types
     * @param string[]   $tags
     * @param string[]   $statuses
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
        ?bool $needLoggedAt = null,
    ): void {
        $indexFields = [];

        $isShortTermIndex = false;

        if (!empty($serviceIds)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('sid');
        }

        if (!is_null($timestampStep)) {
            $isShortTermIndex = true;

            $indexFields[] = new TraceDynamicIndexFieldDto("tss.$timestampStep->value");
        }

        if (!empty($traceIds)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('tid');
        }

        if ($needLoggedAt || !empty($loggedAtFrom) || !empty($loggedAtTo)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('lat');
        }

        if ($types) {
            $indexFields[] = new TraceDynamicIndexFieldDto('tp');
        }

        if ($tags) {
            $isShortTermIndex = true;

            $indexFields[] = new TraceDynamicIndexFieldDto('tgs.nm');
        }

        if ($statuses) {
            $isShortTermIndex = true;

            $indexFields[] = new TraceDynamicIndexFieldDto('st');
        }

        if (!is_null($durationFrom) || !is_null($durationTo)) {
            $isShortTermIndex = true;

            $indexFields[] = new TraceDynamicIndexFieldDto('dur');
        }

        if (!is_null($memoryFrom) || !is_null($memoryTo)) {
            $isShortTermIndex = true;

            $indexFields[] = new TraceDynamicIndexFieldDto('mem');
        }

        if (!is_null($cpuFrom) || !is_null($cpuTo)) {
            $isShortTermIndex = true;

            $indexFields[] = new TraceDynamicIndexFieldDto('cpu');
        }

        if (!is_null($hasProfiling)) {
            $isShortTermIndex = true;

            $indexFields[] = new TraceDynamicIndexFieldDto('hpr');
        }

        if (!is_null($cleared)) {
            $indexFields[] = new TraceDynamicIndexFieldDto('cl');
        }

        foreach ($data->filter ?? [] as $dataFilterItem) {
            $isShortTermIndex = true;

            $indexFields[] = new TraceDynamicIndexFieldDto(
                fieldName: $dataFilterItem->field
            );
        }

        if (!count($indexFields)) {
            return;
        }

        $indexFields = collect($indexFields)
            ->unique(
                fn(TraceDynamicIndexFieldDto $dto) => $dto->fieldName
            )
            ->all();

        if ($isShortTermIndex) {
            $actualUntilAt = now()->addDays(
                $this->shortTermTimeLifeIndexInDays
            );
        } else {
            if ($maxDaysSetting = $this->findMaxDaysSettingAction->handle()) {
                $actualUntilAt = now()->addDays($maxDaysSetting);
            } else {
                $actualUntilAt = now()->addDays(
                    $this->shortTermTimeLifeIndexInDays
                );
            }
        }

        $indexDto = $this->traceDynamicIndexRepository->findOneOrCreate(
            indexData: new TraceDynamicIndexDataDto(
                loggedAtFrom: $loggedAtFrom,
                loggedAtTo: $loggedAtTo,
                fields: $indexFields
            ),
            actualUntilAt: $actualUntilAt
        );

        if (!$indexDto) {
            throw new TraceDynamicIndexNotInitException();
        }

        if ($indexDto->inProcess) {
            throw new TraceDynamicIndexInProcessException();
        }

        if ($indexDto->error) {
            throw new TraceDynamicIndexErrorException(
                $indexDto->error
            );
        }
    }
}
