<?php

namespace App\Modules\Dashboard\Repositories\Database;

use App\Modules\Dashboard\Repositories\Database\Dto\DatabasesDto;

interface DatabaseRepositoryInterface
{
    public function find(): DatabasesDto;
}
