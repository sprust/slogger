<?php

namespace Ifksco\OpenApiGenerator;

use Ifksco\OpenApiGenerator\Router\RouterService;
use Ifksco\OpenApiGenerator\Router\RoutersParser;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use ReflectionException;

class OaService
{
    private static ?string $requestParentClass = null;
    private static ?string $resourcesParentClass = null;

    /**
     * @throws ReflectionException
     */
    public static function generateScheme(bool $public): void
    {
        $parser = new RoutersParser();

        foreach (config('oa-generator.routes') as $name => $pathPrefixes) {
            $parsedRoutes = $parser->parseRoutes(
                (new RouterService($pathPrefixes))->getRoutes()
            );

            $jsonGenerator = new JsonFileGenerator($parsedRoutes);

            $jsonGenerator->generate($public);

            if (!App::runningUnitTests()) {
                $disk = $public ? config('oa-generator.disks.public') : config('oa-generator.disks.private');

                $jsonGenerator->saveToDisk(
                    Storage::disk($disk),
                    "$name-openapi-scheme"
                );
            }
        }
    }

    /**
     * @return class-string<JsonResource>
     */
    public static function getRequestParentClass(): string
    {
        return self::$requestParentClass
            ?: (self::$requestParentClass = config('oa-generator.classes.request_parent_class'));
    }

    /**
     * @return class-string<JsonResource>
     */
    public static function getResourcesParentClass(): string
    {
        return self::$resourcesParentClass
            ?: (self::$resourcesParentClass = config('oa-generator.classes.resources_parent_class'));
    }

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
            && is_subclass_of($class, self::getResourcesParentClass());
    }
}
