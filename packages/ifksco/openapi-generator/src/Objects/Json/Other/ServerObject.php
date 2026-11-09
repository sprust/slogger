<?php

namespace Ifksco\OpenApiGenerator\Objects\Json\Other;

readonly class ServerObject
{
    /**
     * @param ServerVariableObject[]|null $variables
     */
    public function __construct(
        public string $url,
        public ?string $description = null,
        public ?array $variables = null,
    ) {
    }
}
