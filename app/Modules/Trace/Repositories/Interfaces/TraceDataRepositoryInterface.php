<?php

namespace App\Modules\Trace\Repositories\Interfaces;

use App\Modules\Trace\Repositories\Dto\Data\TraceDataItemDto;

interface TraceDataRepositoryInterface
{
    /**
     * @param string[] $keys
     */
    public function syncMany(array $keys): void;

    public function findIdByKey(string $key): ?string;

    /**
     * @param string[] $keys
     *
     * @return TraceDataItemDto[]
     */
    public function findByKeys(array $keys): array;
}
