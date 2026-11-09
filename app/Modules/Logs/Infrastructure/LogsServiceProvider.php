<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure;

use App\Modules\Logs\Infrastructure\Exceptions\LogsExceptionManager;
use App\Modules\Common\Infrastructure\Providers\BaseServiceProvider;
use App\Modules\Logs\Domain\Actions\CleanLogsAction;
use App\Modules\Logs\Domain\Actions\DeleteLogFileAction;
use App\Modules\Logs\Domain\Actions\FindLogEntriesAction;
use App\Modules\Logs\Domain\Actions\FindLogErrorStatAction;
use App\Modules\Logs\Domain\Actions\FindReceiverErrorStatAction;
use App\Modules\Logs\Domain\Actions\FindLogFilesAction;
use App\Modules\Logs\Domain\Actions\IndexLogsAction;
use App\Modules\Logs\Domain\Actions\StreamLogFileAction;
use App\Modules\Logs\Domain\Services\Errors\LogErrorStatCounter;
use App\Modules\Logs\Domain\Services\Files\LogFileFinder;
use App\Modules\Logs\Domain\Services\Formats\LaravelLogFormat;
use App\Modules\Logs\Domain\Services\Formats\LogFormatRegistry;
use App\Modules\Logs\Domain\Services\Formats\LogLevelKeys;
use App\Modules\Logs\Domain\Services\Formats\LogTimeParser;
use App\Modules\Logs\Domain\Services\Formats\NginxAccessLogFormat;
use App\Modules\Logs\Domain\Services\Formats\NginxErrorLogFormat;
use App\Modules\Logs\Domain\Services\Formats\ReceiverLogFormat;
use App\Modules\Logs\Domain\Services\Formats\SconcurAccessLogFormat;
use App\Modules\Logs\Domain\Services\Formats\SconcurJournalLogFormat;
use App\Modules\Logs\Domain\Services\Index\LogIndexBatchRefresher;
use App\Modules\Logs\Domain\Services\Index\LogIndexer;
use App\Modules\Logs\Domain\Services\Reading\LogCursorCodec;
use App\Modules\Logs\Domain\Services\Reading\LogEntriesMerger;
use App\Modules\Logs\Domain\Services\Reading\LogFileStreamFactory;
use App\Modules\Logs\Domain\Services\Reading\LogIndexSearch;
use App\Modules\Logs\Domain\Services\Reading\LogTextReader;
use App\Modules\Logs\Infrastructure\Commands\CleanLogsCommand;
use App\Modules\Logs\Infrastructure\Commands\IndexLogsCommand;
use App\Modules\Logs\Repositories\LogFileRepository;
use App\Modules\Logs\Repositories\LogIndexRepository;
use Illuminate\Console\Scheduling\Schedule;

class LogsServiceProvider extends BaseServiceProvider
{
    public static function setSchedule(Schedule $schedule): void
    {
        $schedule
            ->command(
                command: IndexLogsCommand::class,
            )
            ->everyMinute()
            ->withoutOverlapping();

        $schedule
            ->command(
                command: CleanLogsCommand::class,
            )
            ->daily()
            ->withoutOverlapping();
    }

    protected function getCommands(): array
    {
        return [
            IndexLogsCommand::class,
            CleanLogsCommand::class,
        ];
    }

    protected function getContracts(): array
    {
        return [
            // repositories
            LogFileRepository::class,
            LogIndexRepository::class,
            // services
            LogTimeParser::class,
            LaravelLogFormat::class,
            NginxAccessLogFormat::class,
            NginxErrorLogFormat::class,
            ReceiverLogFormat::class,
            SconcurAccessLogFormat::class,
            SconcurJournalLogFormat::class,
            LogFormatRegistry::class,
            LogFileFinder::class,
            LogIndexer::class,
            LogIndexSearch::class,
            LogTextReader::class,
            LogFileStreamFactory::class,
            LogCursorCodec::class,
            LogEntriesMerger::class,
            LogLevelKeys::class,
            LogIndexBatchRefresher::class,
            LogErrorStatCounter::class,
            FindLogFilesAction::class,
            FindLogEntriesAction::class,
            StreamLogFileAction::class,
            IndexLogsAction::class,
            CleanLogsAction::class,
            DeleteLogFileAction::class,
            // actions
            FindLogErrorStatAction::class,
            FindReceiverErrorStatAction::class,
        ];
    }

    protected function getExceptionManagerClass(): ?string
    {
        return LogsExceptionManager::class;
    }

    protected function getJobs(): array
    {
        return [];
    }

    protected function getListeners(): array
    {
        return [];
    }
}
