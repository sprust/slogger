<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\Actions;

use App\Modules\Setting\Domain\Services\SettingDefinitionFinder;
use App\Modules\Setting\Domain\Services\SettingValueResolver;
use App\Modules\Setting\Enums\SettingKeyEnum;
use App\Modules\Setting\Repositories\SettingRepository;

readonly class FindSettingValueAction
{
    public function __construct(
        private SettingRepository $settingRepository,
        private SettingDefinitionFinder $settingDefinitionFinder,
        private SettingValueResolver $settingValueResolver
    ) {
    }

    public function handle(SettingKeyEnum $key): int
    {
        $definition = $this->settingDefinitionFinder->find($key);

        return $this->settingValueResolver->findStored($definition, $this->settingRepository->findOne($key))
            ?? $this->settingValueResolver->findDefault($definition);
    }
}
