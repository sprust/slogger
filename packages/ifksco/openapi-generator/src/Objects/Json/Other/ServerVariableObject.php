<?php

namespace Ifksco\OpenApiGenerator\Objects\Json\Other;

readonly class ServerVariableObject
{
    public function __construct(
        public string $name,
        public string $default,
        public ?string $description = null,
    ) {
    }
}
