<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\UseCases;

use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Service\Domain\Actions\FindServicesAction;

readonly class FindServicesByAdminUseCase
{
    public function __construct(private FindServicesAction $findServicesAction)
    {
    }

    /**
     * @param int[]|null $ids
     *
     * @return ServiceObject[]
     */
    public function handle(?array $ids = null): array
    {
        return $this->findServicesAction->handle(
            ids: $ids,
        );
    }
}
