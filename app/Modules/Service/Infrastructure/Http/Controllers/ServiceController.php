<?php

declare(strict_types=1);

namespace App\Modules\Service\Infrastructure\Http\Controllers;

use App\Modules\Service\Contracts\Actions\FindServicesActionInterface;
use App\Modules\Service\Infrastructure\Http\Resources\ServiceResource;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class ServiceController
{
    public function __construct(
        private FindServicesActionInterface $findServicesAction
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
