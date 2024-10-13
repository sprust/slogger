<?php

namespace App\Modules\Cleaner\Contracts\Actions;

use App\Modules\Cleaner\Entities\ProcessObject;

interface FindProcessesActionInterface
{
    /**
     * @return ProcessObject[]
     */
    public function handle(int $page, ?int $settingId = null): array;
}
