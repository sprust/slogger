<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\UseCases;

use App\Modules\Setting\Domain\Exceptions\SettingValueOutOfRangeException;
use App\Modules\Setting\Parameters\SettingUpdateParameters;
use App\Modules\Setting\Domain\Actions\UpdateSettingAction;

readonly class UpdateSettingByAdminUseCase
{
    public function __construct(private UpdateSettingAction $updateSettingAction)
    {
    }

    /**
     * @throws SettingValueOutOfRangeException
     */
    public function handle(SettingUpdateParameters $parameters): void
    {
        $this->updateSettingAction->handle($parameters);
    }
}
