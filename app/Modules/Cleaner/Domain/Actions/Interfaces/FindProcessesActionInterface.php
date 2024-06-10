<?php

namespace App\Modules\Cleaner\Domain\Actions\Interfaces;

use App\Modules\Cleaner\Domain\Entities\Objects\ProcessObject;

interface FindProcessesActionInterface
{
    /**
     * @return ProcessObject[]
     */
    public function handle(int $page, ?int $settingId = null): array;
}
