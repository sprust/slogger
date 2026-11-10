<?php

declare(strict_types=1);

namespace App\Modules\Service\Domain\UseCases;

use App\Modules\Service\Domain\Exceptions\ServicesSortMismatchException;
use App\Modules\Service\Parameters\ServicesSortParameters;
use App\Modules\Service\Domain\Actions\SortServicesAction;

readonly class SortServicesByAdminUseCase
{
    public function __construct(private SortServicesAction $sortServicesAction)
    {
    }

    /**
     * @throws ServicesSortMismatchException
     */
    public function handle(ServicesSortParameters $parameters): void
    {
        $this->sortServicesAction->handle($parameters);
    }
}
