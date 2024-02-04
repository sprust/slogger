<?php

namespace Ifksco\OpenApiGenerator\Router;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;

class RouterService
{
    private Router $router;

    public function __construct(private readonly array $pathPrefixes)
    {
        $this->router = app(Router::class);
    }

    public function getRoutes(): array
    {
        $routes = [];

        foreach ($this->router->getRoutes()->getRoutes() as $route) {
            if (!$this->isNeedRoute($route)) {
                continue;
            }

            $routes[] = $route;
        }

        return $routes;
    }

    private function isNeedRoute(Route $route): bool
    {
        foreach ($this->pathPrefixes as $pathPrefix) {
            if (str_starts_with($route->uri(), $pathPrefix)) {
                return true;
            }
        }

        return false;
    }
}
