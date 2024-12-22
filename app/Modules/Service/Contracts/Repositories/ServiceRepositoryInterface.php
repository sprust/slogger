<?php

declare(strict_types=1);

namespace App\Modules\Service\Contracts\Repositories;

use App\Modules\Service\Entities\ServiceObject;

interface ServiceRepositoryInterface
{
    /** @return ServiceObject[] */
    public function find(?array $ids = null): array;

    public function create(string $name, string $uniqueKey): ServiceObject;

    public function findByToken(string $token): ?ServiceObject;

    public function isExistByUniqueKey(string $uniqueKey): bool;
}
