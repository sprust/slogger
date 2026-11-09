<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\UseCases;

use App\Modules\Setting\Entities\SettingObject;
use App\Modules\Setting\Domain\Actions\FindSettingsAction;

readonly class FindSettingsByAdminUseCase
{
    public function __construct(private FindSettingsAction $findSettingsAction)
    {
    }

    /**
     * @return SettingObject[]
     */
    public function handle(): array
    {
        return $this->findSettingsAction->handle();
    }
}
