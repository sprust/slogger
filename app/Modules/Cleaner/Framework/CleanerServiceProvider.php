<?php

namespace App\Modules\Cleaner\Framework;

use App\Modules\Cleaner\Domain\Actions\ClearTracesAction;
use App\Modules\Cleaner\Framework\Commands\ClearTracesCommand;
use App\Modules\Cleaner\Repositories\Interfaces\ProcessRepositoryInterface;
use App\Modules\Cleaner\Repositories\Interfaces\SettingRepositoryInterface;
use App\Modules\Cleaner\Repositories\ProcessRepository;
use App\Modules\Cleaner\Repositories\SettingRepository;
use Illuminate\Support\ServiceProvider;

class CleanerServiceProvider extends ServiceProvider
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
