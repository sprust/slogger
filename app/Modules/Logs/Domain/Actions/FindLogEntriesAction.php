<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Actions;

use App\Modules\Logs\Domain\Services\Files\LogFileFinder;
use App\Modules\Logs\Domain\Services\Formats\LogFormatRegistry;
use App\Modules\Logs\Domain\Services\Formats\LogLevelKeys;
use App\Modules\Logs\Domain\Services\Index\LogIndexBatchRefresher;
use App\Modules\Logs\Domain\Services\Reading\LogCursorCodec;
use App\Modules\Logs\Domain\Services\Reading\LogEntriesMerger;
use App\Modules\Logs\Domain\Services\Reading\LogFileStreamFactory;
use App\Modules\Logs\Entities\Cursor\LogCursorObject;
use App\Modules\Logs\Entities\Cursor\LogCursorPositionObject;
use App\Modules\Logs\Entities\Cursor\LogFilePositionObject;
use App\Modules\Logs\Entities\Cursor\LogFileStartObject;
use App\Modules\Logs\Entities\Entry\LogEntriesPageObject;
use App\Modules\Logs\Entities\Entry\LogEntryViewObject;
use App\Modules\Logs\Entities\Entry\LogLevelKeyCountObject;
use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Entities\Index\LogFileIndexObject;
use App\Modules\Logs\Enums\LogCursorDirectionEnum;
use App\Modules\Logs\Parameters\FindLogEntriesParameters;
use App\Modules\Logs\Parameters\LogEntriesFilterParameters;
use App\Modules\Logs\Repositories\LogFileRepository;
use LogicException;

readonly class FindLogEntriesAction
{
    public function __construct(
        private LogFileFinder $logFileFinder,
        private LogFileRepository $logFileRepository,
        private LogIndexBatchRefresher $logIndexBatchRefresher,
        private LogFileStreamFactory $logFileStreamFactory,
        private LogEntriesMerger $logEntriesMerger,
        private LogCursorCodec $logCursorCodec,
        private LogFormatRegistry $logFormatRegistry,
        private LogLevelKeys $logLevelKeys
    ) {
    }

    public function handle(FindLogEntriesParameters $parameters): LogEntriesPageObject
    {
        $cursor = $parameters->cursor === null
            ? null
            : $this->logCursorCodec->decode($parameters->cursor, $parameters->fileIds);

        $direction = $cursor->direction ?? $parameters->direction;

        $files          = $this->findFiles($parameters->fileIds);
        $missingFileIds = $this->findMissingFileIds($parameters->fileIds, $files);

        $concurrency = max(1, (int) config('module-logs.search.concurrency'));

        $batch = $this->logIndexBatchRefresher->refresh(
            files: $files,
            timeBudgetMs: (int) config('module-logs.search.index_budget_ms'),
            concurrency: $concurrency
        );

        $missingFileIds = [...$missingFileIds, ...$batch->missingFileIds];

        $levelCounts = $this->countLevels($batch->files);

        if ($batch->indexing) {
            return new LogEntriesPageObject(
                items: [],
                levelCounts: $levelCounts,
                total: 0,
                scanned: 0,
                indexing: true,
                indexedBytes: $batch->indexedBytes,
                totalBytes: $batch->totalBytes,
                restartedFileIds: [],
                missingFileIds: $missingFileIds,
                olderCursor: $parameters->cursor,
                newerCursor: null
            );
        }

        $starts = array_map(
            fn(LogFileIndexObject $fileIndex): LogFileStartObject => $this->makeStart($cursor, $fileIndex),
            $batch->files
        );

        $hasLevelFilter = $parameters->levelKeys !== null && count($parameters->levelKeys) > 0;

        $streams = [];

        foreach ($starts as $start) {
            $file = $start->fileIndex->file;

            $streams[] = $this->logFileStreamFactory->make(
                file: $file,
                meta: $start->fileIndex->meta,
                filter: new LogEntriesFilterParameters(
                    levels: $hasLevelFilter
                        ? $this->logLevelKeys->parseKeys($parameters->levelKeys ?? [], $file->type)
                        : null,
                    fromTime: $parameters->fromTime,
                    toTime: $parameters->toTime,
                    searchQuery: $parameters->searchQuery
                ),
                direction: $direction,
                position: $start->position
            );
        }

        $result = $this->logEntriesMerger->merge(
            streams: $streams,
            direction: $direction,
            perPage: $parameters->perPage,
            isSearch: $parameters->searchQuery !== null && $parameters->searchQuery !== '',
            searchBlockRecords: (int) config('module-logs.search.block_records'),
            timeBudgetMs: (int) config('module-logs.search.time_budget_ms'),
            bytesBudget: (int) config('module-logs.search.bytes_budget'),
            concurrency: $concurrency
        );

        $items = [];

        foreach ($result->entries as $entry) {
            $file = $this->findFile($files, $entry->fileId);

            $items[] = new LogEntryViewObject(
                fileId: $file->id,
                type: $file->type,
                entryNo: $entry->entryNo,
                loggedAt: $entry->loggedAt,
                levelKey: $this->logLevelKeys->makeKey($file->type, $entry->level),
                details: $this->logFormatRegistry->get($file->type)->parseEntry($entry->text),
                text: $entry->text,
                truncated: $entry->truncated
            );
        }

        if ($direction === LogCursorDirectionEnum::Newer) {
            $items = array_reverse($items);
        }

        $total   = 0;
        $scanned = 0;

        foreach ($streams as $stream) {
            $total += $stream->getTotal();
            $scanned += $stream->getScannedRecords();
        }

        $continueCursor = $this->makeCursor($batch->files, $direction, $result->positions);

        $backCursor = $this->makeCursor(
            fileIndexes: $batch->files,
            direction: $direction === LogCursorDirectionEnum::Older
                ? LogCursorDirectionEnum::Newer
                : LogCursorDirectionEnum::Older,
            positions: $this->makeBackPositions($starts, $direction)
        );

        $restartedFileIds = [];

        foreach ($starts as $start) {
            if ($start->restarted) {
                $restartedFileIds[] = $start->fileIndex->file->id;
            }
        }

        return new LogEntriesPageObject(
            items: $items,
            levelCounts: $levelCounts,
            total: $total,
            scanned: $scanned,
            indexing: false,
            indexedBytes: $batch->indexedBytes,
            totalBytes: $batch->totalBytes,
            restartedFileIds: $restartedFileIds,
            missingFileIds: $missingFileIds,
            olderCursor: $direction === LogCursorDirectionEnum::Older ? $continueCursor : $backCursor,
            newerCursor: $direction === LogCursorDirectionEnum::Newer ? $continueCursor : $backCursor
        );
    }

    /**
     * @param list<string> $fileIds
     *
     * @return list<LogFileObject>
     */
    private function findFiles(array $fileIds): array
    {
        $known = $this->logFileFinder->findAll();

        $files = [];

        foreach (array_values(array_unique($fileIds)) as $fileId) {
            foreach ($known as $file) {
                if ($file->id === $fileId) {
                    $files[] = $file;

                    break;
                }
            }
        }

        return $files;
    }

    /**
     * @param list<string>        $fileIds
     * @param list<LogFileObject> $files
     *
     * @return list<string>
     */
    private function findMissingFileIds(array $fileIds, array $files): array
    {
        $foundIds = array_map(static fn(LogFileObject $file): string => $file->id, $files);

        return array_values(
            array_diff(array_values(array_unique($fileIds)), $foundIds)
        );
    }

    /**
     * @param list<LogFileObject> $files
     */
    private function findFile(array $files, string $fileId): LogFileObject
    {
        foreach ($files as $file) {
            if ($file->id === $fileId) {
                return $file;
            }
        }

        throw new LogicException(sprintf('File [%s] is not in the scope', $fileId));
    }

    /**
     * @param list<LogFileIndexObject> $fileIndexes
     *
     * @return list<LogLevelKeyCountObject>
     */
    private function countLevels(array $fileIndexes): array
    {
        $counts = [];

        foreach ($fileIndexes as $fileIndex) {
            foreach ($fileIndex->meta->levelCounts as $levelCount) {
                $key = $this->logLevelKeys->makeKey($fileIndex->file->type, $levelCount->level);

                $counts[$key] = ($counts[$key] ?? 0) + $levelCount->count;
            }
        }

        ksort($counts);

        $levelCounts = [];

        foreach ($counts as $key => $count) {
            $levelCounts[] = new LogLevelKeyCountObject(key: (string) $key, count: $count);
        }

        return $levelCounts;
    }

    private function makeStart(?LogCursorObject $cursor, LogFileIndexObject $fileIndex): LogFileStartObject
    {
        $position = $this->findCursorPosition($cursor, $fileIndex->file->id);

        if ($position === null) {
            return new LogFileStartObject(fileIndex: $fileIndex, position: null, restarted: false);
        }

        if ($position->position > $fileIndex->meta->entriesCount || !$this->hasSameHead($fileIndex, $position)) {
            return new LogFileStartObject(fileIndex: $fileIndex, position: null, restarted: true);
        }

        return new LogFileStartObject(fileIndex: $fileIndex, position: $position->position, restarted: false);
    }

    private function findCursorPosition(?LogCursorObject $cursor, string $fileId): ?LogCursorPositionObject
    {
        foreach ($cursor->positions ?? [] as $position) {
            if ($position->fileId === $fileId) {
                return $position;
            }
        }

        return null;
    }

    private function hasSameHead(LogFileIndexObject $fileIndex, LogCursorPositionObject $position): bool
    {
        $meta = $fileIndex->meta;

        if ($meta->headLength === $position->headLength) {
            return $meta->headHash === $position->headHash;
        }

        if ($meta->headLength < $position->headLength) {
            return false;
        }

        $head = $this->logFileRepository->read($fileIndex->file->path, 0, $position->headLength);

        return md5($head) === $position->headHash;
    }

    /**
     * @param list<LogFileStartObject> $starts
     *
     * @return list<LogFilePositionObject>
     */
    private function makeBackPositions(array $starts, LogCursorDirectionEnum $direction): array
    {
        $positions = [];

        foreach ($starts as $start) {
            if ($direction === LogCursorDirectionEnum::Older) {
                $position = $start->position === null ? $start->fileIndex->meta->entriesCount : $start->position + 1;
            } else {
                $position = $start->position === null ? -1 : $start->position - 1;
            }

            $positions[] = new LogFilePositionObject(fileId: $start->fileIndex->file->id, position: $position);
        }

        return $positions;
    }

    /**
     * @param list<LogFileIndexObject>    $fileIndexes
     * @param list<LogFilePositionObject> $positions
     */
    private function makeCursor(array $fileIndexes, LogCursorDirectionEnum $direction, array $positions): ?string
    {
        $cursorPositions = [];
        $hasMore         = false;

        foreach ($positions as $position) {
            foreach ($fileIndexes as $fileIndex) {
                if ($fileIndex->file->id !== $position->fileId) {
                    continue;
                }

                if ($direction === LogCursorDirectionEnum::Newer || $position->position >= 0) {
                    $hasMore = true;
                }

                $cursorPositions[] = new LogCursorPositionObject(
                    fileId: $position->fileId,
                    position: $position->position,
                    headLength: $fileIndex->meta->headLength,
                    headHash: $fileIndex->meta->headHash
                );
            }
        }

        if (!$hasMore) {
            return null;
        }

        return $this->logCursorCodec->encode(
            new LogCursorObject(direction: $direction, positions: $cursorPositions)
        );
    }
}
