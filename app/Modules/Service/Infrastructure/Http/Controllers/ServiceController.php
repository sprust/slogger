<?php

declare(strict_types=1);

namespace App\Modules\Service\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Service\Domain\UseCases\CreateServiceByAdminUseCase;
use App\Modules\Service\Domain\UseCases\FindServiceByAdminUseCase;
use App\Modules\Service\Domain\UseCases\FindServicesByAdminUseCase;
use App\Modules\Service\Domain\UseCases\SortServicesByAdminUseCase;
use App\Modules\Service\Domain\UseCases\UpdateServiceByAdminUseCase;
use App\Modules\Service\Infrastructure\Http\Requests\CreateServiceRequest;
use App\Modules\Service\Infrastructure\Http\Requests\SortServicesRequest;
use App\Modules\Service\Infrastructure\Http\Requests\UpdateServiceRequest;
use App\Modules\Service\Infrastructure\Http\Resources\ServiceDetailResource;
use App\Modules\Service\Infrastructure\Http\Resources\ServiceResource;
use App\Modules\Service\Parameters\ServiceCreateParameters;
use App\Modules\Service\Parameters\ServicesSortParameters;
use App\Modules\Service\Parameters\ServiceUpdateParameters;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class ServiceController
{
    public function __construct(private FindServicesByAdminUseCase $findServicesByAdminUseCase, private FindServiceByAdminUseCase $findServiceByAdminUseCase, private CreateServiceByAdminUseCase $createServiceByAdminUseCase, private UpdateServiceByAdminUseCase $updateServiceByAdminUseCase, private SortServicesByAdminUseCase $sortServicesByAdminUseCase)
    {
    }

    #[OaListItemTypeAttribute(ServiceResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return ServiceResource::collection($this->findServicesByAdminUseCase->handle());
    }

    public function show(int $id): ServiceDetailResource
    {
        $service = $this->findServiceByAdminUseCase->handle($id);

        return new ServiceDetailResource($service);
    }

    public function create(CreateServiceRequest $request): ServiceDetailResource
    {
        $validated = $request->validated();
        $service   = $this->createServiceByAdminUseCase->handle(
            new ServiceCreateParameters(
                name: ArrayValueGetter::string(
                    data: $validated,
                    key: 'name',
                ),
            )
        );

        return new ServiceDetailResource($service);
    }

    public function update(int $id, UpdateServiceRequest $request): ServiceDetailResource
    {
        $validated = $request->validated();
        $service   = $this->updateServiceByAdminUseCase->handle(
            new ServiceUpdateParameters(
                id: $id,
                name: ArrayValueGetter::string(
                    data: $validated,
                    key: 'name',
                ),
            )
        );

        return new ServiceDetailResource($service);
    }

    public function sort(SortServicesRequest $request): void
    {
        $validated = $request->validated();

        $this->sortServicesByAdminUseCase->handle(
            new ServicesSortParameters(
                ids: ArrayValueGetter::arrayInt(
                    data: $validated,
                    key: 'ids',
                ),
            )
        );
    }
}
