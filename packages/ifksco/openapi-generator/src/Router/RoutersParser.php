<?php

namespace Ifksco\OpenApiGenerator\Router;

use Ifksco\OpenApiGenerator\Objects\ParsedRoute;
use Illuminate\Routing\Route;

class RoutersParser
{
    /**
     * @param array<Route> $routes
     *
     * @return array<ParsedRoute>
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
