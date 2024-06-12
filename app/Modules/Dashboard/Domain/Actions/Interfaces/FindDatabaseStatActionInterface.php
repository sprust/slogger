<?php

namespace App\Modules\Dashboard\Domain\Actions\Interfaces;

use App\Modules\Dashboard\Domain\Entities\Objects\DatabaseStatObject;

interface FindDatabaseStatActionInterface
{
    /**
     * @return DatabaseStatObject[]
     */
    public function handle(): array;
}
