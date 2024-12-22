<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Contracts\Actions;

use App\Modules\Dashboard\Entities\DatabaseStatObject;

interface FindDatabaseStatActionInterface
{
    /**
     * @return DatabaseStatObject[]
     */
    public function handle(): array;
}
