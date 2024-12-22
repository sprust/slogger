<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure;

use App\Modules\Cleaner\Infrastructure\Commands\ClearTracesCommand;
use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Logs\Domain\Actions\CreateLogAction;
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
            // actions
            CreateLogAction::class,
        ];
    }
}
