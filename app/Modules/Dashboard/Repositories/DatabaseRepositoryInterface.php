<?php

namespace App\Modules\Dashboard\Repositories;

use App\Modules\Dashboard\Dto\Objects\Database\DatabaseObjects;

interface DatabaseRepositoryInterface
{
    public function index(): DatabaseObjects;
}
