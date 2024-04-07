<?php

namespace App\Modules\Service\Framework\Http\Controllers;

use App\Modules\Service\Domain\Actions\FindServicesAction;
use App\Modules\Service\Framework\Http\Resources\ServiceResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class ServiceController
{
    public function __construct(
        private FindServicesAction $findServicesAction
    ) {
    }

    #[OaListItemTypeAttribute(ServiceResource::class)]
    public function index(): AnonymousResourceCollection
    {
        return ServiceResource::collection(
            $this->findServicesAction->handle()
        );
    }
}
