<?php

declare(strict_types=1);

namespace App\Modules\Setting\Infrastructure;

use App\Modules\Common\Infrastructure\BaseServiceProvider;
use App\Modules\Setting\Domain\Actions\FindSettingsAction;
use App\Modules\Setting\Domain\Actions\FindSettingValueAction;
use App\Modules\Setting\Domain\Actions\ResetSettingAction;
use App\Modules\Setting\Domain\Actions\UpdateSettingAction;
use App\Modules\Setting\Domain\Services\SettingDefinitionFinder;
use App\Modules\Setting\Domain\Services\SettingValueResolver;
use App\Modules\Setting\Repositories\SettingRepository;

class SettingServiceProvider extends BaseServiceProvider
{
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
}
