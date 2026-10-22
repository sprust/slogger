<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Services\Files;

use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Entities\File\LogSourceObject;
use App\Modules\Logs\Enums\LogTypeEnum;
use App\Modules\Logs\Repositories\LogFileRepository;
use App\Modules\Setting\Domain\Actions\FindSettingValueAction;
use App\Modules\Setting\Enums\SettingKeyEnum;

readonly class LogFileFinder
{
    public function __construct(
        private LogFileRepository $logFileRepository,
        private FindSettingValueAction $findSettingValueAction
    ) {
    }

    /**
     * @return list<LogFileObject>
     */
    public function findAll(): array
    {
        $files = [];

        foreach ($this->makeSources() as $source) {
            foreach ($this->keepNewest($this->logFileRepository->findInSource($source)) as $file) {
                $files[$file->id] ??= $file;
            }
        }

        return array_values($files);
    }

    public function findById(string $id): ?LogFileObject
    {
        foreach ($this->findAll() as $file) {
            if ($file->id === $id) {
                return $file;
            }
        }

        return null;
    }

    /**
     * The newest file of a source is the one being written: deleted, it takes the rest of
     * the day's entries with it.
     *
     * @param list<LogFileObject> $files
     *
     * @return list<LogFileObject>
     */
    private function keepNewest(array $files): array
    {
        $newest = null;

        foreach ($files as $file) {
            if ($newest === null || $file->modifiedAtMs > $newest->modifiedAtMs) {
                $newest = $file;
            }
        }

        if ($newest === null || !$newest->deletable) {
            return $files;
        }

        return array_map(
            static fn(LogFileObject $file): LogFileObject => $file !== $newest ? $file : new LogFileObject(
                id: $file->id,
                path: $file->path,
                name: $file->name,
                source: $file->source,
                folder: $file->folder,
                type: $file->type,
                sizeBytes: $file->sizeBytes,
                modifiedAtMs: $file->modifiedAtMs,
                keepDays: $file->keepDays,
                deletable: false
            ),
            $files
        );
    }

    /**
     * @return list<LogSourceObject>
     */
    private function makeSources(): array
    {
        $sources = [];

        foreach ((array) config('module-logs.sources') as $source) {
            $type = $source['type'];

            $sources[] = new LogSourceObject(
                name: (string) $source['name'],
                folder: rtrim((string) $source['folder'], '/'),
                pattern: (string) $source['pattern'],
                type: $type instanceof LogTypeEnum ? $type : LogTypeEnum::from((string) $type),
                keepDays: $this->makeKeepDays($source),
                deletable: (bool) ($source['deletable'] ?? false)
            );
        }

        return $sources;
    }

    /**
     * @param array<string, mixed> $source
     */
    private function makeKeepDays(array $source): ?int
    {
        $setting = $source['keep_days_setting'] ?? null;

        if ($setting instanceof SettingKeyEnum) {
            return $this->findSettingValueAction->handle($setting);
        }

        return isset($source['keep_days']) ? (int) $source['keep_days'] : null;
    }
}
