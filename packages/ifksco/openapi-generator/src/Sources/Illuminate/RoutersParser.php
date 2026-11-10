<?php

namespace Ifksco\OpenApiGenerator\Sources\Illuminate;

use Ifksco\OpenApiGenerator\Objects\PathObject;
use Illuminate\Routing\Route;
use ReflectionException;

class RoutersParser
{
    /**
     * @param array<Route> $routes
     *
     * @return array<PathObject>
     *
     * @throws ReflectionException
     */
    public function parseRoutes(array $routes): array
    {
        $parsedRoutes = [];

        foreach ($routes as $route) {
            if ($route->getActionName() === 'Closure') {
                continue;
            }

            $parser = new RouterParser($route);

            $parsedRoutes[] = $parser->parse();
        }

        return $parsedRoutes;
    }
}
