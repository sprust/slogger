<?php

namespace Ifksco\OpenApiGenerator\Objects;

use Ifksco\OpenApiGenerator\Json\Properties\OaArrayProperty;
use Ifksco\OpenApiGenerator\Json\Schemes\OaRequestScheme;
use Ifksco\OpenApiGenerator\Json\Schemes\OaScheme;

class ParsedRoute
{
    public ?string $summary = null;

    public string $uri;

    /** @var array<string> */
    public array $methods;
    /** @var array<string> */
    public array $middlewares;

    /** @var array<string> */
    public array $pathParameters;

    public ?OaRequestScheme $requestScheme;

    /** @var array<int, OaScheme|OaArrayProperty> */
    public array $responses;
}
