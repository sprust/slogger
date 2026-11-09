<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Infrastructure;

use App\Modules\Cleaner\Domain\Actions\ClearTracesAction;
use App\Modules\Cleaner\Domain\Actions\FindProcessesAction;
use App\Modules\Cleaner\Infrastructure\Commands\ClearTracesCommand;
use App\Modules\Cleaner\Repositories\ProcessRepository;
use App\Modules\Cleaner\Infrastructure\Jobs\ClearTracesJob;
use App\Modules\Common\Infrastructure\Providers\BaseServiceProvider;
use App\Modules\Common\Domain\Services\DispatcherInterface;
use Illuminate\Console\Scheduling\Schedule;

class CleanerServiceProvider extends BaseServiceProvider
{
    public static function setSchedule(Schedule $schedule): void
    {
        $schedule
            ->call(
                callback: static function (DispatcherInterface $dispatcher): void {
                    $dispatcher->dispatch(
                        new ClearTracesJob()
                    );
                },
            )
            ->name(ClearTracesJob::class)
            ->hourly();
    }

    protected function getCommands(): array
    {
        return [ClearTracesCommand::class];
    }

    protected function getJobs(): array
    {
        return [ClearTracesJob::class];
    }

    protected function getContracts(): array
    {
        return [
            // repositories
            ProcessRepository::class,
            // actions
            ClearTracesAction::class,
            FindProcessesAction::class,
        ];
    }

    protected function getListeners(): array
    {
        return [];
    }

    protected function getExceptionManagerClass(): ?string
    {
        return null;
    }
}
