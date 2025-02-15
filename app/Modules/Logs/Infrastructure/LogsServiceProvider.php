<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure;

use App\Modules\Cleaner\Infrastructure\Commands\ClearTracesCommand;
use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Logs\Contracts\Actions\CreateLogActionInterface;
use App\Modules\Logs\Contracts\Actions\PaginateLogsActionInterface;
use App\Modules\Logs\Contracts\Repositories\LogRepositoryInterface;
use App\Modules\Logs\Domain\Actions\CreateLogAction;
use App\Modules\Logs\Domain\Actions\PaginateLogsAction;
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
            LogRepositoryInterface::class      => LogRepository::class,
            // actions
            CreateLogActionInterface::class    => CreateLogAction::class,
            PaginateLogsActionInterface::class => PaginateLogsAction::class,
        ];
    }
}
