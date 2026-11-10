<?php

namespace Ifksco\OpenApiGenerator\Sources\Illuminate\Custom;

use Ifksco\OpenApiGenerator\Sources\Illuminate\Converters\Responses\CustomResponseProperty;
use Ifksco\OpenApiGenerator\Sources\Illuminate\RouterParser;

interface OaBaseCustomResponseInterface
{
    public function is(string $returnedType): bool;

    /** @return CustomResponseProperty[] */
    public function make(RouterParser $routerParser): array;
}
