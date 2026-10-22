<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\Services;

use App\Modules\Setting\Entities\SettingDefinitionObject;
use App\Modules\Setting\Entities\SettingValueObject;

readonly class SettingValueResolver
{
    public function isValid(SettingDefinitionObject $definition, int $value): bool
    {
        return $value >= $definition->min && $value <= $definition->max;
    }

    public function findDefault(SettingDefinitionObject $definition): int
    {
        return (int) config($definition->configKey);
    }

    public function findStored(SettingDefinitionObject $definition, ?SettingValueObject $stored): ?int
    {
        if (is_null($stored) || !$this->isValid($definition, $stored->value)) {
            return null;
        }

        return $stored->value;
    }
}
