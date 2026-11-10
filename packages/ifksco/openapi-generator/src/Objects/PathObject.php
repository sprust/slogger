<?php

namespace Ifksco\OpenApiGenerator\Objects;

use Ifksco\OpenApiGenerator\Objects\Json\Properties\OaArrayProperty;
use Ifksco\OpenApiGenerator\Objects\Json\Schemes\OaRequestScheme;
use Ifksco\OpenApiGenerator\Objects\Json\Schemes\OaScheme;

class PathObject
{
    public ?string $summary = null;

    public string $uri;

    /**
     * @var string[]
     */
    public array $methods;

    public bool $isSecurity;

    /**
     * @var string[]
     */
    public array $pathParameters;

    public ?OaRequestScheme $requestScheme;

    public ?array $requestExample = null;

    /**
     * @var array<int, OaScheme|OaArrayProperty>
     */
    public array $responses;

    /**
     * @var array<string, mixed>
     */
    public array $extensions = [];
}
