<?php

namespace Ifksco\OpenApiGenerator\Sources\Illuminate;

use Ifksco\OpenApiGenerator\Sources\SourceInterface;
use ReflectionException;

abstract class BaseRouterParser implements SourceInterface
{
    /**
     * @return string[]
     */
    abstract protected function getPathPrefixes(): array;

    public function __construct(
        private readonly RoutersParser $routersParser
    ) {
    }

    /**
     * @throws ReflectionException
     */
    public function getPaths(): array
    {
        return $this->routersParser->parseRoutes(
            (new RouterService(
                $this->getPathPrefixes()
            ))->getRoutes()
        );
    }
}
