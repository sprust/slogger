<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Reading;

use App\Modules\Logs\Entities\Cursor\LogFilePositionObject;
use App\Modules\Logs\Entities\Entry\LogMergeHeadObject;
use App\Modules\Logs\Entities\Entry\LogMergeResultObject;
use App\Modules\Logs\Enums\LogCursorDirectionEnum;
use SConcur\WaitGroup;

readonly class LogEntriesMerger
{
    /**
     * @param list<LogFileStream> $streams
     */
    public function merge(
        array $streams,
        LogCursorDirectionEnum $direction,
        int $perPage,
        bool $isSearch,
        int $searchBlockRecords,
        int $timeBudgetMs,
        int $bytesBudget,
        int $concurrency
    ): LogMergeResultObject {
        $startedAt = hrtime(true);

        $slots = array_map(
            static fn(LogFileStream $stream): LogMergeSlot => new LogMergeSlot($stream),
            $streams
        );

        $page = [];

        $hasAdvanced = false;

        while (count($page) < $perPage) {
            $heads = $this->findHeads($slots);

            if (count($heads) === 0) {
                break;
            }

            usort($heads, fn(LogMergeHeadObject $left, LogMergeHeadObject $right): int => $this->compare($left, $right, $direction));

            if ($heads[0]->isPending) {
                $slot = $slots[$heads[0]->slotIndex];

                $page[] = $slot->pending[0];

                $slot->pending = array_slice($slot->pending, 1);

                continue;
            }

            if ($hasAdvanced && $this->isBudgetSpent($streams, $startedAt, $timeBudgetMs, $bytesBudget)) {
                break;
            }

            $toAdvance = [];

            foreach ($heads as $head) {
                if ($head->isPending || count($toAdvance) >= max(1, $concurrency)) {
                    break;
                }

                $toAdvance[] = $slots[$head->slotIndex];
            }

            $this->advance(
                slots: $toAdvance,
                blockRecords: $isSearch ? max(1, $searchBlockRecords) : $perPage - count($page)
            );

            $hasAdvanced = true;
        }

        $positions = [];

        foreach ($slots as $slot) {
            $positions[] = new LogFilePositionObject(
                fileId: $slot->stream->getFile()->id,
                position: count($slot->pending) === 0
                    ? $slot->stream->getContinuePosition()
                    : $slot->pending[0]->entryNo
            );
        }

        return new LogMergeResultObject(entries: $page, positions: $positions);
    }

    /**
     * @param list<LogMergeSlot> $slots
     *
     * @return list<LogMergeHeadObject>
     */
    private function findHeads(array $slots): array
    {
        $heads = [];

        foreach ($slots as $index => $slot) {
            if (count($slot->pending) > 0) {
                $heads[] = new LogMergeHeadObject(
                    slotIndex: $index,
                    time: $slot->pending[0]->loggedAt,
                    isPending: true
                );

                continue;
            }

            $nextTime = $slot->stream->getNextTime();

            if ($nextTime !== null) {
                $heads[] = new LogMergeHeadObject(
                    slotIndex: $index,
                    time: $nextTime,
                    isPending: false
                );
            }
        }

        return $heads;
    }

    private function compare(LogMergeHeadObject $left, LogMergeHeadObject $right, LogCursorDirectionEnum $direction): int
    {
        if ($left->time !== $right->time) {
            return $direction === LogCursorDirectionEnum::Older
                ? $right->time <=> $left->time
                : $left->time <=> $right->time;
        }

        return $direction === LogCursorDirectionEnum::Older
            ? $left->slotIndex <=> $right->slotIndex
            : $right->slotIndex <=> $left->slotIndex;
    }

    /**
     * @param list<LogFileStream> $streams
     */
    private function isBudgetSpent(array $streams, int $startedAt, int $timeBudgetMs, int $bytesBudget): bool
    {
        if ($timeBudgetMs > 0 && (hrtime(true) - $startedAt) / 1_000_000 >= $timeBudgetMs) {
            return true;
        }

        if ($bytesBudget <= 0) {
            return false;
        }

        $scannedBytes = 0;

        foreach ($streams as $stream) {
            $scannedBytes += $stream->getScannedBytes();
        }

        return $scannedBytes >= $bytesBudget;
    }

    /**
     * @param list<LogMergeSlot> $slots
     */
    private function advance(array $slots, int $blockRecords): void
    {
        if (count($slots) === 1) {
            $slots[0]->pending = $slots[0]->stream->next($blockRecords);

            return;
        }

        $waitGroup = WaitGroup::create();

        foreach ($slots as $slot) {
            $waitGroup->add(
                static function () use ($slot, $blockRecords): void {
                    $slot->pending = $slot->stream->next($blockRecords);
                }
            );
        }

        $waitGroup->waitAll();
    }
}
