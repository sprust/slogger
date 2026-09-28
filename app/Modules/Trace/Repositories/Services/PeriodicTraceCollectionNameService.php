<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use LogicException;

readonly class PeriodicTraceCollectionNameService
{
    /**
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
                            && $collectionToHour <= $fromHour
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

    public function makeHourStart(string $collectionName): Carbon
    {
        $hourStart = Carbon::createFromFormat(
            'Y_m_d H',
            sprintf('%s %s', mb_substr($collectionName, 7, 10), mb_substr($collectionName, 18, 2)),
            'UTC'
        );

        if (!$hourStart instanceof Carbon) {
            throw new LogicException("Trace collection name [$collectionName] has no hour");
        }

        return $hourStart->startOfHour();
    }

    private function makeDayString(Carbon $datetime): string
    {
        return $datetime->format('Y_m_d');
    }
}
