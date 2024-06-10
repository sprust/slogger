<?php

namespace App\Modules\Cleaner\Framework;

use App\Modules\Cleaner\Domain\Actions\ClearTracesAction;
use App\Modules\Cleaner\Domain\Actions\CreateSettingAction;
use App\Modules\Cleaner\Domain\Actions\DeleteSettingAction;
use App\Modules\Cleaner\Domain\Actions\FindProcessesAction;
use App\Modules\Cleaner\Domain\Actions\FindSettingByIdAction;
use App\Modules\Cleaner\Domain\Actions\FindSettingsAction;
use App\Modules\Cleaner\Domain\Actions\Interfaces\ClearTracesActionInterface;
use App\Modules\Cleaner\Domain\Actions\Interfaces\CreateSettingActionInterface;
use App\Modules\Cleaner\Domain\Actions\Interfaces\DeleteSettingActionInterface;
use App\Modules\Cleaner\Domain\Actions\Interfaces\FindProcessesActionInterface;
use App\Modules\Cleaner\Domain\Actions\Interfaces\FindSettingByIdActionInterface;
use App\Modules\Cleaner\Domain\Actions\Interfaces\FindSettingsActionInterface;
use App\Modules\Cleaner\Domain\Actions\Interfaces\UpdateSettingActionInterface;
use App\Modules\Cleaner\Domain\Actions\UpdateSettingAction;
use App\Modules\Cleaner\Framework\Commands\ClearTracesCommand;
use App\Modules\Cleaner\Repositories\Interfaces\ProcessRepositoryInterface;
use App\Modules\Cleaner\Repositories\Interfaces\SettingRepositoryInterface;
use App\Modules\Cleaner\Repositories\ProcessRepository;
use App\Modules\Cleaner\Repositories\SettingRepository;
use App\Modules\Common\Framework\BaseServiceProvider;

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
