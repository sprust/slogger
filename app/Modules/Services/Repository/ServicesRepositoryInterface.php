<?php

namespace App\Modules\Services\Repository;

use App\Models\Services\Service;
use App\Modules\Services\Dto\Parameters\ServiceCreateParameters;

interface ServicesRepositoryInterface
{
    public function create(ServiceCreateParameters $parameters): Service;

    public function findByToken(string $token): ?Service;

    public function isExistByUniqueKey(string $uniqueKey): bool;
}
