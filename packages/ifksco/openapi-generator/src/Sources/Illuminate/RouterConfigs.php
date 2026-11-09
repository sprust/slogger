<?php

namespace Ifksco\OpenApiGenerator\Sources\Illuminate;

use Ifksco\OpenApiGenerator\Sources\Illuminate\Custom\OaPathExtensionsResolverInterface;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;

class RouterConfigs
{
    public static function isClassRequest(mixed $class): bool
    {
        return is_string($class)
            && class_exists($class)
            && is_subclass_of($class, self::getRequestParentClass());
    }

    public static function isClassResource(mixed $class): bool
    {
        return is_string($class)
            && class_exists($class)
            && Arr::first(
                self::getResourcesParentClasses(),
                fn(string $resourceClass) => is_subclass_of($class, $resourceClass)
            );
    }

    public static function isSecurityRoute(Route $route): bool
    {
        $middlewares = array_diff($route->middleware(), $route->excludedMiddleware());

        if (!$middlewares) {
            return false;
        }

        $securityMiddlewares = self::getSecurityMiddlewares();

        foreach ($middlewares as $routeMiddleware) {
            if (in_array($routeMiddleware, $securityMiddlewares)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getPathExtensions(Route $route): array
    {
        $result = [];

        foreach (self::getPathExtensionsResolvers() as $resolver) {
            $result = [...$result, ...$resolver->resolve($route)];
        }

        return $result;
    }

    /**
     * @return class-string<JsonResource>
     */
    private static function getRequestParentClass(): string
    {
        return config('oa-generator.classes.request_parent_class');
    }

    /**
     * @return class-string<JsonResource>[]
     */
    private static function getResourcesParentClasses(): array
    {
        return config('oa-generator.classes.resources_parent_classes');
    }

    private static function getSecurityMiddlewares(): ?array
    {
        return config('oa-generator.security_middlewares');
    }

    /**
     * @return OaPathExtensionsResolverInterface[]
     */
    private static function getPathExtensionsResolvers(): array
    {
        return array_map(
            static fn(string $resolverClass) => app($resolverClass),
            config(
                'oa-generator.custom.path_extensions',
                []
            )
        );
    }
}
