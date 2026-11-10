<?php

declare(strict_types=1);

namespace Tests\Services\OpenApi\Fixtures;

use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;

class UnwrappedController
{
    #[OaListItemTypeAttribute(TextResource::class)]
    public function __invoke(): UnwrappedCollection
    {
        return new UnwrappedCollection([]);
    }
}
