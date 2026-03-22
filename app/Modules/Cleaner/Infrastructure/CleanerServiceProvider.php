<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Infrastructure;

use App\Modules\Cleaner\Contracts\Actions\ClearTracesActionInterface;
use App\Modules\Cleaner\Contracts\Actions\FindProcessesActionInterface;
use App\Modules\Cleaner\Contracts\Repositories\ProcessRepositoryInterface;
use App\Modules\Cleaner\Domain\Actions\ClearTracesAction;
use App\Modules\Cleaner\Domain\Actions\FindProcessesAction;
use App\Modules\Cleaner\Infrastructure\Commands\ClearTracesCommand;
use App\Modules\Cleaner\Repositories\ProcessRepository;
use App\Modules\Common\Infrastructure\BaseServiceProvider;

class CleanerServiceProvider extends BaseServiceProvider
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
            ProcessRepositoryInterface::class        => ProcessRepository::class,
            // actions
            ClearTracesActionInterface::class        => ClearTracesAction::class,
            FindProcessesActionInterface::class      => FindProcessesAction::class,
        ];
    }
}
