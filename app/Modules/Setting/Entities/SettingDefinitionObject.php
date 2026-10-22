<?php

declare(strict_types=1);

namespace App\Modules\Setting\Entities;

use App\Modules\Setting\Enums\SettingKeyEnum;

readonly class SettingDefinitionObject
{
    public function __construct(
        public SettingKeyEnum $key,
        public string $envName,
        public string $configKey,
        public int $min,
        public int $max,
        public string $description
    ) {
    }
}
