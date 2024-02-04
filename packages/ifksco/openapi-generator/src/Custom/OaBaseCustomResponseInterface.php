<?php

namespace Ifksco\OpenApiGenerator\Custom;

use Ifksco\OpenApiGenerator\Converters\Responses\CustomResponseProperty;
use Ifksco\OpenApiGenerator\Router\RouterParser;

interface OaBaseCustomResponseInterface
{
    public function is(string $returnedType): bool;

    /** @return CustomResponseProperty[] */
    public function make(RouterParser $routerParser): array;
}
