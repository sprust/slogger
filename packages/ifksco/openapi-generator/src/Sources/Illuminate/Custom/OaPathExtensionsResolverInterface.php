<?php

namespace Ifksco\OpenApiGenerator\Sources\Illuminate\Custom;

use Illuminate\Routing\Route;

interface OaPathExtensionsResolverInterface
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(Route $route): array;
}
