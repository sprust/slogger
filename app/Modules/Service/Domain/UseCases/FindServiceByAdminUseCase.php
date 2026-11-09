<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\UseCases;

use App\Modules\Service\Domain\Exceptions\ServiceNotFoundException;
use App\Modules\Service\Entities\ServiceObject;
use App\Modules\Service\Domain\Actions\FindServiceAction;

readonly class FindServiceByAdminUseCase
{
    public function __construct(private FindServiceAction $findServiceAction)
    {
    }

    /**
     * @throws ServiceNotFoundException
     */
    public function handle(int $id): ServiceObject
    {
        return $this->findServiceAction->handle(
            id: $id,
        ) ?? throw new ServiceNotFoundException($id);
    }
}
