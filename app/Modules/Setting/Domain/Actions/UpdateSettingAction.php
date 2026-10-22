<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\Actions;

use App\Modules\Setting\Domain\Exceptions\SettingValueOutOfRangeException;
use App\Modules\Setting\Domain\Services\SettingDefinitionFinder;
use App\Modules\Setting\Domain\Services\SettingValueResolver;
use App\Modules\Setting\Parameters\SettingUpdateParameters;
use App\Modules\Setting\Repositories\SettingRepository;

readonly class UpdateSettingAction
{
    public function __construct(
        private SettingRepository $settingRepository,
        private SettingDefinitionFinder $settingDefinitionFinder,
        private SettingValueResolver $settingValueResolver
    ) {
    }

    /**
     * @throws SettingValueOutOfRangeException
     */
    public function handle(SettingUpdateParameters $parameters): void
    {
        $definition = $this->settingDefinitionFinder->find($parameters->key);

        if (!$this->settingValueResolver->isValid($definition, $parameters->value)) {
            throw new SettingValueOutOfRangeException($definition, $parameters->value);
        }

        $this->settingRepository->set($parameters->key, $parameters->value);
    }
}
