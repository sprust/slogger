<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\Actions;

use App\Modules\Setting\Domain\Services\SettingDefinitionFinder;
use App\Modules\Setting\Domain\Services\SettingValueResolver;
use App\Modules\Setting\Entities\SettingDefinitionObject;
use App\Modules\Setting\Entities\SettingObject;
use App\Modules\Setting\Repositories\SettingRepository;

readonly class FindSettingsAction
{
    public function __construct(
        private SettingRepository $settingRepository,
        private SettingDefinitionFinder $settingDefinitionFinder,
        private SettingValueResolver $settingValueResolver
    ) {
    }

    /**
     * @return SettingObject[]
     */
    public function handle(): array
    {
        $stored = [];

        foreach ($this->settingRepository->find() as $value) {
            $stored[$value->key->value] = $value;
        }

        return array_map(
            function (SettingDefinitionObject $definition) use ($stored): SettingObject {
                $storedValue  = $stored[$definition->key->value] ?? null;
                $defaultValue = $this->settingValueResolver->findDefault($definition);
                $value        = $this->settingValueResolver->findStored($definition, $storedValue);

                return new SettingObject(
                    definition: $definition,
                    value: $value ?? $defaultValue,
                    defaultValue: $defaultValue,
                    isOverridden: !is_null($value),
                    updatedAt: $storedValue?->updatedAt
                );
            },
            $this->settingDefinitionFinder->findAll()
        );
    }
}
