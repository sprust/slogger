<?php

declare(strict_types=1);

namespace App\Modules\Service\Infrastructure\Http\Controllers;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Service\Domain\Actions\CreateServiceAction;
use App\Modules\Service\Domain\Actions\FindServiceAction;
use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Domain\Actions\SortServicesAction;
use App\Modules\Service\Domain\Actions\UpdateServiceAction;
use App\Modules\Service\Domain\Exceptions\ServiceAlreadyExistsException;
use App\Modules\Service\Domain\Exceptions\ServiceNotFoundException;
use App\Modules\Service\Domain\Exceptions\ServicesSortMismatchException;
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
use Symfony\Component\HttpFoundation\Response as ResponseFoundation;

readonly class ServiceController
{
    public function __construct(
        private FindServicesAction $findServicesAction,
        private FindServiceAction $findServiceAction,
        private CreateServiceAction $createServiceAction,
        private UpdateServiceAction $updateServiceAction,
        private SortServicesAction $sortServicesAction
    ) {
    }

    #[OaListItemTypeAttribute(ServiceResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return ServiceResource::collection(
            $this->findServicesAction->handle()
        );
    }

    public function show(int $id): ServiceDetailResource
    {
        $service = $this->findServiceAction->handle($id);

        if (is_null($service)) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, "Service [$id] not found");
        }

        return new ServiceDetailResource($service);
    }

    public function create(CreateServiceRequest $request): ServiceDetailResource
    {
        $validated = $request->validated();

        try {
            $service = $this->createServiceAction->handle(
                new ServiceCreateParameters(
                    name: ArrayValueGetter::string($validated, 'name')
                )
            );
        } catch (ServiceAlreadyExistsException $exception) {
            abort(ResponseFoundation::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }

        return new ServiceDetailResource($service);
    }

    public function update(int $id, UpdateServiceRequest $request): ServiceDetailResource
    {
        $validated = $request->validated();

        try {
            $this->updateServiceAction->handle(
                new ServiceUpdateParameters(
                    id: $id,
                    name: ArrayValueGetter::string($validated, 'name')
                )
            );
        } catch (ServiceNotFoundException $exception) {
            abort(ResponseFoundation::HTTP_NOT_FOUND, $exception->getMessage());
        } catch (ServiceAlreadyExistsException $exception) {
            abort(ResponseFoundation::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }

        return $this->show($id);
    }

    public function sort(SortServicesRequest $request): void
    {
        $validated = $request->validated();

        try {
            $this->sortServicesAction->handle(
                new ServicesSortParameters(
                    ids: ArrayValueGetter::arrayInt($validated, 'ids')
                )
            );
        } catch (ServicesSortMismatchException $exception) {
            abort(ResponseFoundation::HTTP_UNPROCESSABLE_ENTITY, $exception->getMessage());
        }
    }
}
