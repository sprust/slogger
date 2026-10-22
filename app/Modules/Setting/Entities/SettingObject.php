<?php

declare(strict_types=1);

namespace App\Modules\Setting\Entities;

use Illuminate\Support\Carbon;

readonly class SettingObject
{
    public function __construct(
        public SettingDefinitionObject $definition,
        public int $value,
        public int $defaultValue,
        public bool $isOverridden,
        public ?Carbon $updatedAt
    ) {
    }
}
