<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure;

use App\Modules\Cleaner\Infrastructure\Commands\ClearTracesCommand;
use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Logs\Domain\Actions\CreateLogAction;
use App\Modules\Logs\Domain\Actions\FindLogErrorStatAction;
use App\Modules\Logs\Domain\Actions\PaginateLogsAction;
use App\Modules\Logs\Domain\Services\Files\LogFileFinder;
use App\Modules\Logs\Domain\Services\Formats\LaravelLogFormat;
use App\Modules\Logs\Domain\Services\Formats\LogFormatRegistry;
use App\Modules\Logs\Domain\Services\Formats\LogTimeParser;
use App\Modules\Logs\Domain\Services\Formats\NginxAccessLogFormat;
use App\Modules\Logs\Domain\Services\Formats\NginxErrorLogFormat;
use App\Modules\Logs\Domain\Services\Index\LogIndexer;
use App\Modules\Logs\Repositories\LogFileRepository;
use App\Modules\Logs\Repositories\LogIndexRepository;
use App\Modules\Logs\Repositories\LogRepository;

class LogsServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        $this->commands([
            ClearTracesCommand::class,
        ]);
    }

    protected function getContracts(): array
    {
        return [
            // repositories
            LogRepository::class,
            LogFileRepository::class,
            LogIndexRepository::class,
            // services
            LogTimeParser::class,
            LaravelLogFormat::class,
            NginxAccessLogFormat::class,
            NginxErrorLogFormat::class,
            LogFormatRegistry::class,
            LogFileFinder::class,
            LogIndexer::class,
            // actions
            CreateLogAction::class,
            FindLogErrorStatAction::class,
            PaginateLogsAction::class,
        ];
    }
}
