<?php

declare(strict_types=1);

namespace App\Modules\Setting\Parameters;

use App\Modules\Setting\Enums\SettingKeyEnum;

readonly class SettingUpdateParameters
{
    public function __construct(
        public SettingKeyEnum $key,
        public int $value
    ) {
    }
}
