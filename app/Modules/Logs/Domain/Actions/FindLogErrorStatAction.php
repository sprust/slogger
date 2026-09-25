<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Actions;

use App\Modules\Common\Domain\Exceptions\MutexLockTimeoutException;
use App\Modules\Logs\Domain\Exceptions\LogFileNotFoundException;
use App\Modules\Logs\Domain\Services\Files\LogFileFinder;
use App\Modules\Logs\Domain\Services\Formats\LogFormatRegistry;
use App\Modules\Logs\Domain\Services\Index\LogIndexer;
use App\Modules\Logs\Domain\Services\Index\LogLevelCounter;
use App\Modules\Logs\Domain\Services\Reading\LogTextReader;
use App\Modules\Logs\Entities\File\LogFileObject;
use App\Modules\Logs\Entities\Index\LogIndexMetaObject;
use App\Modules\Logs\Entities\Index\LogIndexRecordObject;
use App\Modules\Logs\Entities\Log\LogLevelStatObject;
use App\Modules\Logs\Enums\LaravelLogLevelEnum;
use App\Modules\Logs\Enums\LogTypeEnum;
use App\Modules\Logs\Repositories\LogIndexRepository;
use Illuminate\Support\Carbon;

readonly class FindLogErrorStatAction
{
    private const array ERROR_LEVELS = [
        LaravelLogLevelEnum::Error,
        LaravelLogLevelEnum::Critical,
        LaravelLogLevelEnum::Alert,
        LaravelLogLevelEnum::Emergency,
    ];

    private const int MAX_MESSAGE_BYTES = 64 * 1024;
    private const int SCAN_RECORDS      = 50_000;

    public function __construct(
        private LogFileFinder $logFileFinder,
        private LogIndexer $logIndexer,
        private LogIndexRepository $logIndexRepository,
        private LogTextReader $logTextReader,
        private LogFormatRegistry $logFormatRegistry
    ) {
    }

    public function handle(Carbon $since, Carbon $until): LogLevelStatObject
    {
        $sinceTime = $since->getTimestamp();
        $untilTime = $until->getTimestamp();

        $count = 0;

        $lastFile   = null;
        $lastRecord = null;

        foreach ($this->logFileFinder->findAll() as $file) {
            if ($file->type !== LogTypeEnum::Laravel || intdiv($file->modifiedAtMs, 1000) < $sinceTime) {
                continue;
            }

            $meta = $this->findMeta($file);

            if ($meta === null) {
                continue;
            }

            $levelCounter = new LogLevelCounter($meta->levelCounts);

            foreach (self::ERROR_LEVELS as $level) {
                $levelCount = $levelCounter->get($level->value);

                for ($from = 0; $from < $levelCount; $from += self::SCAN_RECORDS) {
                    $records = $this->logIndexRepository->readRecords(
                        fileId: $file->id,
                        level: $level->value,
                        from: $from,
                        count: min(self::SCAN_RECORDS, $levelCount - $from)
                    );

                    foreach ($records as $record) {
                        if ($record->loggedAt <= $sinceTime || $record->loggedAt > $untilTime) {
                            continue;
                        }

                        ++$count;

                        if ($lastRecord === null || $this->isLater($record, $file, $lastRecord, $lastFile)) {
                            $lastFile   = $file;
                            $lastRecord = $record;
                        }
                    }
                }
            }
        }

        return new LogLevelStatObject(
            count: $count,
            lastMessage: $lastFile === null || $lastRecord === null ? null : $this->readMessage($lastFile, $lastRecord)
        );
    }

    private function findMeta(LogFileObject $file): ?LogIndexMetaObject
    {
        try {
            return $this->logIndexer->ensureFresh(file: $file, waitForLockSec: (int) config('module-logs.errors.wait_for_lock_sec'));
        } catch (LogFileNotFoundException) {
            return null;
        } catch (MutexLockTimeoutException) {
            return $this->logIndexRepository->findMeta($file->id);
        }
    }

    private function isLater(
        LogIndexRecordObject $record,
        LogFileObject $file,
        LogIndexRecordObject $lastRecord,
        ?LogFileObject $lastFile
    ): bool {
        if ($record->loggedAt !== $lastRecord->loggedAt) {
            return $record->loggedAt > $lastRecord->loggedAt;
        }

        if ($lastFile === null || $file->id !== $lastFile->id) {
            return $lastFile === null || $file->modifiedAtMs > $lastFile->modifiedAtMs;
        }

        return $record->entryNo > $lastRecord->entryNo;
    }

    private function readMessage(LogFileObject $file, LogIndexRecordObject $record): ?string
    {
        foreach ($this->logTextReader->read($file->path, [$record], self::MAX_MESSAGE_BYTES) as $entryText) {
            return $this->logFormatRegistry->get($file->type)->parseEntry($entryText->text)->message;
        }

        return null;
    }
}
