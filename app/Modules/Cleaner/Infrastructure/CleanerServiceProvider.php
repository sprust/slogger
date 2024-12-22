<?php

declare(strict_types=1);

namespace App\Modules\Cleaner\Infrastructure;

use App\Modules\Cleaner\Contracts\Actions\ClearTracesActionInterface;
use App\Modules\Cleaner\Contracts\Actions\CreateSettingActionInterface;
use App\Modules\Cleaner\Contracts\Actions\DeleteSettingActionInterface;
use App\Modules\Cleaner\Contracts\Actions\FindProcessesActionInterface;
use App\Modules\Cleaner\Contracts\Actions\FindSettingByIdActionInterface;
use App\Modules\Cleaner\Contracts\Actions\FindSettingsActionInterface;
use App\Modules\Cleaner\Contracts\Actions\UpdateSettingActionInterface;
use App\Modules\Cleaner\Contracts\Repositories\ProcessRepositoryInterface;
use App\Modules\Cleaner\Contracts\Repositories\SettingRepositoryInterface;
use App\Modules\Cleaner\Domain\Actions\ClearTracesAction;
use App\Modules\Cleaner\Domain\Actions\CreateSettingAction;
use App\Modules\Cleaner\Domain\Actions\DeleteSettingAction;
use App\Modules\Cleaner\Domain\Actions\FindProcessesAction;
use App\Modules\Cleaner\Domain\Actions\FindSettingByIdAction;
use App\Modules\Cleaner\Domain\Actions\FindSettingsAction;
use App\Modules\Cleaner\Domain\Actions\UpdateSettingAction;
use App\Modules\Cleaner\Infrastructure\Commands\ClearTracesCommand;
use App\Modules\Cleaner\Repositories\ProcessRepository;
use App\Modules\Cleaner\Repositories\SettingRepository;
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
            ProcessRepositoryInterface::class => ProcessRepository::class,
            SettingRepositoryInterface::class => SettingRepository::class,
            // actions
            ClearTracesActionInterface::class     => ClearTracesAction::class,
            CreateSettingActionInterface::class   => CreateSettingAction::class,
            DeleteSettingActionInterface::class   => DeleteSettingAction::class,
            FindProcessesActionInterface::class   => FindProcessesAction::class,
            FindSettingByIdActionInterface::class => FindSettingByIdAction::class,
            FindSettingsActionInterface::class    => FindSettingsAction::class,
            UpdateSettingActionInterface::class   => UpdateSettingAction::class,
        ];
    }
}
