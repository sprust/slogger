<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\UseCases;

use App\Modules\Setting\Enums\SettingKeyEnum;
use App\Modules\Setting\Domain\Actions\ResetSettingAction;

readonly class ResetSettingByAdminUseCase
{
    public function __construct(private ResetSettingAction $resetSettingAction)
    {
    }

    public function handle(SettingKeyEnum $key): void
    {
        $this->resetSettingAction->handle(
            key: $key,
        );
    }
}
