<?php

declare(strict_types=1);

namespace App\Modules\Setting\Infrastructure;

use App\Modules\Setting\Infrastructure\Exceptions\SettingExceptionManager;
use App\Modules\Common\Infrastructure\Providers\BaseServiceProvider;
use App\Modules\Setting\Domain\Actions\FindSettingsAction;
use App\Modules\Setting\Domain\Actions\FindSettingValueAction;
use App\Modules\Setting\Domain\Actions\ResetSettingAction;
use App\Modules\Setting\Domain\Actions\UpdateSettingAction;
use App\Modules\Setting\Domain\Services\SettingDefinitionFinder;
use App\Modules\Setting\Domain\Services\SettingValueResolver;
use App\Modules\Setting\Repositories\SettingRepository;
use Illuminate\Console\Scheduling\Schedule;

class SettingServiceProvider extends BaseServiceProvider
{
    public static function setSchedule(Schedule $schedule): void
    {
    }

    protected function getContracts(): array
    {
        return [
            // repositories
            SettingRepository::class,
            // services
            SettingDefinitionFinder::class,
            SettingValueResolver::class,
            // actions
            FindSettingsAction::class,
            FindSettingValueAction::class,
            ResetSettingAction::class,
            UpdateSettingAction::class,
        ];
    }

    protected function getExceptionManagerClass(): ?string
    {
        return SettingExceptionManager::class;
    }

    protected function getCommands(): array
    {
        return [];
    }

    protected function getJobs(): array
    {
        return [];
    }

    protected function getListeners(): array
    {
        return [];
    }
}
