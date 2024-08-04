<?php

namespace App\Modules\Cleaner\Domain\Actions\Interfaces;

use App\Modules\Cleaner\Domain\Entities\Objects\SettingObject;
use App\Modules\Cleaner\Domain\Exceptions\SettingAlreadyExistsException;

interface CreateSettingActionInterface
{
    /**
     * @throws SettingAlreadyExistsException
     */
    public function handle(int $daysLifetime, ?string $type, bool $onlyData): SettingObject;
}
