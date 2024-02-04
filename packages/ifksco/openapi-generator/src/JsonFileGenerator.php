<?php

namespace Ifksco\OpenApiGenerator;

use Ifksco\OpenApiGenerator\Json\Properties\OaArrayProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaObjectProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaPaginationInfoProperty;
use Ifksco\OpenApiGenerator\Json\Schemes\OaRequestScheme;
use Ifksco\OpenApiGenerator\Objects\ParsedRoute;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

class JsonFileGenerator
{
    /** @var array<ParsedRoute> */
    private array $parsedRoutes;

    private array $jsonData = [];

    private array $securityMiddlewares;

    public function __construct(array $parsedRoutes)
    {
        $this->parsedRoutes        = $parsedRoutes;
        $this->securityMiddlewares = config('oa-generator.security_middlewares');
    }

    public function generate(bool $public): void
    {
        $this->jsonData = [];

        $paths = [];
        foreach ($this->parsedRoutes as $parsedRoute) {
            $uri = "/$parsedRoute->uri";

            if (!array_key_exists($uri, $paths)) {
                $paths[$uri] = [];
            }

            $method = strtolower($parsedRoute->methods[0]);

            $parameters = [];

            if ($parsedRoute->summary) {
                $paths[$uri][$method]['summary'] = $parsedRoute->summary;
            }

            if ($parsedRoute->pathParameters) {
                foreach ($parsedRoute->pathParameters as $parameter) {
                    $parameters[] = [
                        'name'     => trim($parameter, '?'),
                        'required' => !str_ends_with($parameter, '?'),
                        'in'       => 'path',
                        'schema'   => (object) [],
                    ];
                }
            }

            if ($parsedRoute->requestScheme) {
                if (Str::upper($method) !== 'GET') {
                    $paths[$uri][$method]['requestBody'] = $this->defineRequestBody($parsedRoute);
                } else {
                    if ($parsedRoute->requestScheme->oneOf) {
                        throw new RuntimeException(
                            "Route '$parsedRoute->uri': this for GET operations not implemented yet"
                        );
                    }

                    foreach ($parsedRoute->requestScheme->properties as $fieldName => $property) {
                        $parameters[] = [
                            'name'     => $fieldName,
                            'required' => $property->required ?? false,
                            'in'       => 'query',
                            'schema'   => $property->toArray(),
                        ];
                    }
                }
            }

            if ($parameters) {
                $paths[$uri][$method]['parameters'] = $parameters;
            }

            $isDeleteMethod = Str::upper($method) === 'DELETE';

            $paths[$uri][$method]['responses'] = (object) $this->defineResponses($parsedRoute, $isDeleteMethod);

            if ($isDeleteMethod && ($paths[$uri][$method]['requestBody'] ?? false)) {
                throw new RuntimeException('DELETE operations cannot have a requestBody.');
            }

            if ($this->hasSecurityMiddleware($parsedRoute)) {
                $paths[$uri][$method]['security'][] = [
                    'bearerAuth' => [],
                ];
            }
        }

        $this->jsonData = [
            'openapi'    => '3.0.0',
            'info'       => [
                'title'   => 'zb-hoteliers-api-scheme',
                'version' => '0.1',
            ],
            'components' => [
                'securitySchemes' => [
                    'bearerAuth' => [
                        'type'         => 'http',
                        'scheme'       => 'bearer',
                        'bearerFormat' => 'JWT',
                    ],
                ],
            ],
            'paths'      => $paths,
        ];

        if ($public) {
            $this->jsonData['servers'] = config('app.open_api.servers');
        }
    }

    private function defineRequestBody(ParsedRoute $parsedRoute): array
    {
        $contentType = $parsedRoute->requestScheme?->contentType ?? OaRequestScheme::OA_CONTENT_TYPE_JSON;

        return [
            'required' => (bool) $parsedRoute->requestScheme,
            'content'  => [
                $contentType => [
                    'schema' => (object) ($parsedRoute->requestScheme?->toArray() ?? []),
                ],
            ],
        ];
    }

    private function defineResponses(ParsedRoute $parsedRoute, bool $isDeleteMethod): array
    {
        $responses = [];

        $wrap = OaService::getResourcesParentClass()::$wrap;

        foreach ($parsedRoute->responses ?: [200 => null] as $statusCode => $schema) {
            if ($isDeleteMethod && $schema) {
                throw new RuntimeException('DELETE operations cannot have a responseBody.');
            }

            $responses[$statusCode] = [
                'description' => 'description',
            ];

            if (!$schema) {
                $schemaJson = (object) [];
            } else {
                $wrapProperty           = new OaObjectProperty();
                $wrapProperty->required = true;

                $wrapProperty->properties         = [
                    $wrap => (object) $schema->toArray(),
                ];
                $wrapProperty->requiredProperties = [
                    $wrap,
                ];

                if ($schema instanceof OaArrayProperty && $schema->isPagination) {
                    $wrapProperty->properties['meta']   = (new OaPaginationInfoProperty())->toArray();
                    $wrapProperty->requiredProperties[] = 'meta';
                }

                $schemaJson = $wrapProperty->toArray();
            }

            if (!$isDeleteMethod) {
                $responses[$statusCode]['content'] = [
                    'application/json' => [
                        'schema' => $schemaJson,
                    ],
                ];
            }
        }

        return $responses;
    }

    public function saveToDisk(Filesystem $filesystem, string $fileName): void
    {
        $json = json_encode($this->jsonData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $filesystem->put("$fileName.json", $json);
    }

    private function hasSecurityMiddleware(ParsedRoute $parsedRoute): bool
    {
        foreach ($parsedRoute->middlewares as $routeMiddleware) {
            if (in_array($routeMiddleware, $this->securityMiddlewares)) {
                return true;
            }
        }

        return false;
    }
}
