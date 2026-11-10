<?php

namespace Ifksco\OpenApiGenerator\Sources;

use BackedEnum;
use Ifksco\OpenApiGenerator\Objects\Json\Other\ServerObject;
use Ifksco\OpenApiGenerator\Objects\PathObject;

interface SourceInterface
{
    public function getName(): string;

    public function getTitle(): string;

    public function getVersion(): string;

    public function getDescription(bool $isPublic): ?string;

    /**
     * @return ServerObject[]
     */
    public function getServers(): array;

    /**
     * @return PathObject[]
     */
    public function getPaths(): array;

    /**
     * @return class-string<BackedEnum>[]
     */
    public function getAdditionalEnums(): array;
}
