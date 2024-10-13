<?php

namespace App\Modules\Cleaner\Contracts\Actions;

use App\Modules\Cleaner\Domain\Exceptions\SettingAlreadyExistsException;
use App\Modules\Cleaner\Entities\SettingObject;

interface CreateSettingActionInterface
{
    /**
     * @throws SettingAlreadyExistsException
     */
    public function handle(int $daysLifetime, ?string $type, bool $onlyData): SettingObject;
}
