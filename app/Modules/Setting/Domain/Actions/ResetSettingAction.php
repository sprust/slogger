<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\Actions;

use App\Modules\Setting\Enums\SettingKeyEnum;
use App\Modules\Setting\Repositories\SettingRepository;

readonly class ResetSettingAction
{
    public function __construct(
        private SettingRepository $settingRepository
    ) {
    }

    public function handle(SettingKeyEnum $key): void
    {
        $this->settingRepository->delete($key);
    }
}
