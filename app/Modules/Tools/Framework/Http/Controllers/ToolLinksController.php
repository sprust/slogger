<?php

namespace App\Modules\Tools\Framework\Http\Controllers;

use App\Modules\Tools\Framework\Http\Resources\ToolLinksResource;
use App\Modules\Tools\Framework\Services\ToolLinksService;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class ToolLinksController
{
    public function __construct(
        private ToolLinksService $toolLinksService
    ) {
    }

    #[OaListItemTypeAttribute(ToolLinksResource::class)]
    public function __invoke(): AnonymousResourceCollection
    {
        return ToolLinksResource::collection(
            $this->toolLinksService->get()
        );
    }
}
