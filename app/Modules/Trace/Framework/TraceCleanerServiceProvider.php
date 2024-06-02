<?php

namespace App\Modules\Trace\Framework;

use App\Modules\Trace\Domain\Actions\ClearTracesAction;
use App\Modules\Trace\Framework\Commands\ClearTracesCommand;
use App\Modules\Trace\Repositories\Interfaces\ProcessRepositoryInterface;
use App\Modules\Trace\Repositories\Interfaces\SettingRepositoryInterface;
use App\Modules\Trace\Repositories\ProcessRepository;
use App\Modules\Trace\Repositories\SettingRepository;
use Illuminate\Support\ServiceProvider;

class TraceCleanerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerRepositories();

        $this->app->singleton(ClearTracesAction::class);

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
    }
}
