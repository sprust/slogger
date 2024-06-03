<?php

namespace App\Modules\Service\Repositories;

use App\Modules\Service\Repositories\Dto\ServiceDto;

interface ServiceRepositoryInterface
{
    /** @return ServiceDto[] */
    public function find(): array;

    public function create(string $name, string $uniqueKey): ServiceDto;

    public function findByToken(string $token): ?ServiceDto;

    public function isExistByUniqueKey(string $uniqueKey): bool;
}
