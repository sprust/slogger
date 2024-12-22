<?php

declare(strict_types=1);

namespace App\Modules\Service\Contracts\Actions;

use App\Modules\Service\Entities\ServiceObject;

interface FindServicesActionInterface
{
    /**
     * @param int[]|null $ids
     *
     * @return ServiceObject[]
     */
    public function handle(?array $ids = null): array;
}
