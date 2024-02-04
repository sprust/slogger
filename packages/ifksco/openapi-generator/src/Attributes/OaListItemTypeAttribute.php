<?php

namespace Ifksco\OpenApiGenerator\Attributes;

use Attribute;

#[Attribute]
readonly class OaListItemTypeAttribute
{
    public function __construct(
        private string $className,
        private bool $isPagination = false,
        private bool $isRecursive = false
    ) {
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function isPagination(): bool
    {
        return $this->isPagination;
    }

    public function isRecursive(): bool
    {
        return $this->isRecursive;
    }
}
