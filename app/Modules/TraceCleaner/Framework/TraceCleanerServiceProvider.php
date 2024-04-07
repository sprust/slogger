<?php

namespace App\Modules\TraceCleaner\Framework;

use App\Modules\TraceCleaner\Domain\Actions\ClearAction;
use App\Modules\TraceCleaner\Framework\Commands\ClearTracesCommand;
use App\Modules\TraceCleaner\Repositories\Interfaces\ProcessRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Interfaces\SettingRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Interfaces\TraceRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Interfaces\TraceTreeRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\ProcessRepository;
use App\Modules\TraceCleaner\Repositories\SettingRepository;
use App\Modules\TraceCleaner\Repositories\TraceRepository;
use App\Modules\TraceCleaner\Repositories\TraceTreeRepository;
use Illuminate\Support\ServiceProvider;

class TraceCleanerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRepositories();

        $this->app->singleton(ClearAction::class);

        $this->commands([
            ClearTracesCommand::class,
        ]);
    }

    private function registerRepositories(): void
    {
        $this->app->singleton(
            ProcessRepositoryInterface::class,
            ProcessRepository::class
        );
        $this->app->singleton(
            SettingRepositoryInterface::class,
            SettingRepository::class
        );
        $this->app->singleton(
            TraceRepositoryInterface::class,
            TraceRepository::class
        );
        $this->app->singleton(
            TraceTreeRepositoryInterface::class,
            TraceTreeRepository::class
        );
    }
}
