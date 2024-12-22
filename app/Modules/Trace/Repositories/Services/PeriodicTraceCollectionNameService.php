<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;

readonly class PeriodicTraceCollectionNameService
{
    public function __construct(private int $hoursStep)
    {
    }

    public function newByDatetime(Carbon $datetime): string
    {
        $date = $this->makeDayString($datetime);

        $hourFrom = (int) floor($datetime->hour / $this->hoursStep) * $this->hoursStep;
        $hourTo   = $hourFrom + $this->hoursStep - 1;

        $hourFromFormatted = sprintf('%02d', $hourFrom);
        $hourToFormatted   = sprintf('%02d', $hourTo);

        return "traces_{$date}_{$hourFromFormatted}_$hourToFormatted";
    }

    /**
     * TODO: need more tests
     *
     * @param string[] $collectionNames
     *
     * @return string[]
     */
    public function filterCollectionNamesByPeriod(
        array $collectionNames,
        ?Carbon $from = null,
        ?Carbon $to = null
    ): array {
        if ($to && $from?->gt($to)) {
            return [];
        }

        $allCollectionNames = array_values(
            Arr::sort(
                array_filter(
                    $collectionNames,
                    static fn(string $collectionName) => str_starts_with($collectionName, 'traces_')
                )
            )
        );

        $allCollectionNamesCount = count($allCollectionNames);

        if (!$allCollectionNamesCount) {
            return [];
        }

        if (!$from && !$to) {
            return $allCollectionNames;
        }

        $fromDay  = $from ? $this->makeDayString($from) : null;
        $fromHour = $from?->hour;
        $hasFrom  = !is_null($fromDay) && !is_null($fromHour);

        $toDay  = $to ? $this->makeDayString($to) : null;
        $toHour = $to?->hour;
        $hasTo  = !is_null($toDay) && !is_null($toHour);

        return array_values(
            array_filter(
                $allCollectionNames,
                static function (string $collectionName) use (
                    $hasFrom,
                    $fromDay,
                    $fromHour,
                    $hasTo,
                    $toDay,
                    $toHour
                ) {
                    $collectionNameDay  = mb_substr($collectionName, 7, 10);
                    $collectionFromHour = (int) mb_substr($collectionName, 18, 2);
                    $collectionToHour   = (int) mb_substr($collectionName, 21, 2);

                    if ($hasFrom) {
                        if ($fromDay > $collectionNameDay) {
                            return false;
                        }

                        if ($collectionNameDay === $fromDay
                            && $collectionFromHour < $fromHour
                            && $collectionToHour < $fromHour
                        ) {
                            return false;
                        }
                    }

                    if ($hasTo) {
                        if ($toDay < $collectionNameDay) {
                            return false;
                        }

                        if ($collectionNameDay === $toDay
                            && $collectionFromHour > $toHour
                            && $collectionToHour > $toHour
                        ) {
                            return false;
                        }
                    }

                    return true;
                }
            )
        );
    }

    private function makeDayString(Carbon $datetime): string
    {
        return $datetime->format('Y_m_d');
    }
}
