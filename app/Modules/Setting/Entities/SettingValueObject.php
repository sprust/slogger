<?php

declare(strict_types=1);

namespace App\Modules\Setting\Entities;

use App\Modules\Setting\Enums\SettingKeyEnum;
use Illuminate\Support\Carbon;

readonly class SettingValueObject
{
    public function __construct(
        public SettingKeyEnum $key,
        public int $value,
        public Carbon $updatedAt
    ) {
    }
}
