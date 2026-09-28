<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Domain\Actions\Bridges;

use App\Modules\Logs\Domain\Actions\FindLogEntriesAction;
use App\Modules\Logs\Domain\Actions\FindLogFilesAction;
use App\Modules\Logs\Domain\Services\Formats\LogFormatRegistry;
use App\Modules\Logs\Entities\Entry\LogEntryViewObject;
use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Enums\LogCursorDirectionEnum;
use App\Modules\Logs\Parameters\FindLogEntriesParameters;
use App\Modules\Mcp\Domain\Exceptions\McpLogLevelNotFoundException;
use App\Modules\Mcp\Domain\Exceptions\McpLogSourceNotFoundException;
use App\Modules\Mcp\Entities\Bridges\McpLogEntriesObject;
use App\Modules\Mcp\Entities\Bridges\McpLogEntryObject;
use App\Modules\Mcp\Parameters\FindMcpLogEntriesParameters;
use Illuminate\Support\Carbon;

readonly class FindMcpLogEntriesAction
{
    public const int MAX_LIMIT = 50;

    public function __construct(
        private FindLogFilesAction $findLogFilesAction,
        private FindLogEntriesAction $findLogEntriesAction,
        private LogFormatRegistry $logFormatRegistry
    ) {
    }

    /**
     * @throws McpLogSourceNotFoundException
     * @throws McpLogLevelNotFoundException
     */
    public function handle(FindMcpLogEntriesParameters $parameters): McpLogEntriesObject
    {
        $allFiles = $this->findLogFilesAction->handle();

        $files = array_values(
            array_filter(
                $allFiles,
                static fn(LogFileObject $file) => mb_strtolower($file->source) === mb_strtolower($parameters->source)
            )
        );

        if (count($files) === 0) {
            throw new McpLogSourceNotFoundException(
                array_values(array_unique(array_map(static fn(LogFileObject $file) => $file->source, $allFiles)))
            );
        }

        $page = $this->findLogEntriesAction->handle(
            new FindLogEntriesParameters(
                fileIds: array_map(static fn(LogFileObject $file) => $file->id, $files),
                levelKeys: count($parameters->levels) > 0 ? $this->levelKeys($files, $parameters->levels) : null,
                fromTime: $parameters->from?->getTimestamp(),
                toTime: $parameters->to?->getTimestamp(),
                searchQuery: $parameters->query,
                cursor: null,
                direction: LogCursorDirectionEnum::Older,
                perPage: max(1, min($parameters->limit, self::MAX_LIMIT))
            )
        );

        return new McpLogEntriesObject(
            indexing: $page->indexing,
            total: $page->total,
            entries: array_map(
                fn(LogEntryViewObject $entry) => new McpLogEntryObject(
                    time: Carbon::createFromTimestampUTC($entry->loggedAt),
                    level: $this->levelName($entry->levelKey),
                    file: $this->fileName($files, $entry->fileId),
                    text: $entry->text
                ),
                $page->items
            )
        );
    }

    /**
     * @param LogFileObject[] $files
     * @param string[]        $levels
     *
     * @return list<string>
     *
     * @throws McpLogLevelNotFoundException
     */
    private function levelKeys(array $files, array $levels): array
    {
        $types = [];

        foreach ($files as $file) {
            $types[$file->type->value] = $file->type;
        }

        $keys  = [];
        $known = [];

        foreach ($levels as $level) {
            $found = false;

            foreach ($types as $type) {
                foreach ($this->logFormatRegistry->get($type)->getLevelNames() as $levelName) {
                    $known[] = $levelName->name;

                    if (mb_strtolower($levelName->name) === mb_strtolower($level)) {
                        $keys[] = "$type->value.$levelName->name";
                        $found  = true;
                    }
                }
            }

            if (!$found) {
                throw new McpLogLevelNotFoundException($level, array_values(array_unique($known)));
            }
        }

        return $keys;
    }

    private function levelName(string $levelKey): string
    {
        $position = strpos($levelKey, '.');

        return $position === false ? $levelKey : substr($levelKey, $position + 1);
    }

    /**
     * @param LogFileObject[] $files
     */
    private function fileName(array $files, string $fileId): string
    {
        foreach ($files as $file) {
            if ($file->id === $fileId) {
                return $file->name;
            }
        }

        return $fileId;
    }
}
