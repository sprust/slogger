<?php

declare(strict_types=1);

namespace App\Modules\Tools\Infrastructure\Http\Controllers;

use App\Modules\Tools\Infrastructure\Http\Resources\ToolLinksResource;
use App\Modules\Tools\Domain\UseCases\FindToolLinksByAdminUseCase;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

readonly class ToolLinksController
{
    public function __construct(private FindToolLinksByAdminUseCase $findToolLinksByAdminUseCase)
    {
    }

    #[OaListItemTypeAttribute(ToolLinksResource::class)]
    public function __invoke(): AnonymousResourceCollection
    {
        return ToolLinksResource::collection($this->findToolLinksByAdminUseCase->handle());
    }
}
