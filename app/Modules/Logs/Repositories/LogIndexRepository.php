<?php

declare(strict_types=1);

namespace App\Modules\Logs\Repositories;

use App\Modules\Logs\Entities\Index\LogIndexMetaObject;
use App\Modules\Logs\Entities\Index\LogIndexRecordObject;
use App\Modules\Logs\Enums\LogTypeEnum;
use SConcur\Features\Files\Files;
use SConcur\Features\Files\FileWriteMode;

readonly class LogIndexRepository
{
    public const int RECORD_BYTES = 21;

    private const string RECORD_PACK   = 'NJNNC';
    private const string RECORD_UNPACK = 'NentryNo/Joffset/Nlength/NloggedAt/Clevel';

    private string $indexPath;

    public function __construct()
    {
        $this->indexPath = rtrim((string) config('module-logs.index.path'), '/');
    }

    public function findMeta(string $fileId): ?LogIndexMetaObject
    {
        $path = $this->makeMetaPath($fileId);

        if (!Files::exists(path: $path)) {
            return null;
        }

        $data = json_decode(Files::read(path: $path), true);

        if (!is_array($data)) {
            return null;
        }

        $type = LogTypeEnum::tryFrom((string) ($data['type'] ?? ''));

        if ($type === null) {
            return null;
        }

        $levelCounts = [];

        foreach ((array) ($data['levelCounts'] ?? []) as $level => $count) {
            $levelCounts[(int) $level] = (int) $count;
        }

        return new LogIndexMetaObject(
            version: (int) ($data['version'] ?? 0),
            path: (string) ($data['path'] ?? ''),
            type: $type,
            indexedBytes: (int) ($data['indexedBytes'] ?? 0),
            lastEntryOpen: (bool) ($data['lastEntryOpen'] ?? false),
            headLength: (int) ($data['headLength'] ?? 0),
            headHash: (string) ($data['headHash'] ?? ''),
            entriesCount: (int) ($data['entriesCount'] ?? 0),
            levelCounts: $levelCounts
        );
    }

    public function saveMeta(string $fileId, LogIndexMetaObject $meta): void
    {
        $this->makeDirectory($fileId);

        Files::writeAtomic(
            path: $this->makeMetaPath($fileId),
            contents: (string) json_encode([
                'version'       => $meta->version,
                'path'          => $meta->path,
                'type'          => $meta->type->value,
                'indexedBytes'  => $meta->indexedBytes,
                'lastEntryOpen' => $meta->lastEntryOpen,
                'headLength'    => $meta->headLength,
                'headHash'      => $meta->headHash,
                'entriesCount'  => $meta->entriesCount,
                'levelCounts'   => (object) $meta->levelCounts,
            ])
        );
    }

    /**
     * @param list<LogIndexRecordObject> $records
     */
    public function appendRecords(string $fileId, array $records): void
    {
        if ($records === []) {
            return;
        }

        $this->makeDirectory($fileId);

        $all    = [];
        $levels = [];

        foreach ($records as $record) {
            $packed = pack(
                self::RECORD_PACK,
                $record->entryNo,
                $record->offset,
                $record->length,
                $record->loggedAt,
                $record->level
            );

            $all[] = $packed;

            $levels[$record->level][] = $packed;
        }

        Files::write(
            path: $this->makeRecordsPath($fileId, null),
            contents: implode('', $all),
            mode: FileWriteMode::Append
        );

        foreach ($levels as $level => $packedRecords) {
            Files::write(
                path: $this->makeRecordsPath($fileId, $level),
                contents: implode('', $packedRecords),
                mode: FileWriteMode::Append
            );
        }
    }

    public function countRecords(string $fileId, ?int $level): int
    {
        $stat = Files::stat(path: $this->makeRecordsPath($fileId, $level));

        if (!$stat->exists) {
            return 0;
        }

        return intdiv($stat->sizeBytes, self::RECORD_BYTES);
    }

    /**
     * @return list<LogIndexRecordObject>
     */
    public function readRecords(string $fileId, ?int $level, int $from, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $data = Files::read(
            path: $this->makeRecordsPath($fileId, $level),
            offsetBytes: $from * self::RECORD_BYTES,
            lengthBytes: $count * self::RECORD_BYTES,
            maxReadBytes: 0
        );

        $records = [];

        $length = strlen($data) - strlen($data) % self::RECORD_BYTES;

        for ($position = 0; $position < $length; $position += self::RECORD_BYTES) {
            /** @var array{entryNo: int, offset: int, length: int, loggedAt: int, level: int} $values */
            $values = unpack(self::RECORD_UNPACK, $data, $position);

            $records[] = new LogIndexRecordObject(
                entryNo: $values['entryNo'],
                offset: $values['offset'],
                length: $values['length'],
                loggedAt: $values['loggedAt'],
                level: $values['level']
            );
        }

        return $records;
    }

    /**
     * @return list<int>
     */
    public function findLevels(string $fileId): array
    {
        if (!Files::exists(path: $this->makeDirectoryPath($fileId))) {
            return [];
        }

        $levels = [];

        foreach (Files::list(path: $this->makeDirectoryPath($fileId), pattern: 'level-*.idx') as $entry) {
            if (preg_match('/^level-(\d+)\.idx$/', $entry->name, $match) === 1) {
                $levels[] = (int) $match[1];
            }
        }

        sort($levels);

        return $levels;
    }

    public function truncateRecords(string $fileId, ?int $level, int $count): void
    {
        $path = $this->makeRecordsPath($fileId, $level);

        if (!Files::exists(path: $path)) {
            return;
        }

        Files::truncate(path: $path, sizeBytes: $count * self::RECORD_BYTES);
    }

    public function delete(string $fileId): void
    {
        Files::removeDirectory(
            path: $this->makeDirectoryPath($fileId),
            recursive: true,
            missingOk: true
        );
    }

    private function makeDirectory(string $fileId): void
    {
        Files::makeDirectory(path: $this->makeDirectoryPath($fileId), recursive: true);
    }

    private function makeDirectoryPath(string $fileId): string
    {
        return sprintf('%s/%s', $this->indexPath, $fileId);
    }

    private function makeMetaPath(string $fileId): string
    {
        return sprintf('%s/meta.json', $this->makeDirectoryPath($fileId));
    }

    private function makeRecordsPath(string $fileId, ?int $level): string
    {
        return $level === null
            ? sprintf('%s/entries.idx', $this->makeDirectoryPath($fileId))
            : sprintf('%s/level-%d.idx', $this->makeDirectoryPath($fileId), $level);
    }
}
