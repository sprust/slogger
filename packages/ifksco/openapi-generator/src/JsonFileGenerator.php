<?php

namespace Ifksco\OpenApiGenerator;

use BackedEnum;
use Ifksco\OpenApiGenerator\Objects\Json\Other\ServerObject;
use Ifksco\OpenApiGenerator\Objects\Json\Properties\OaArrayProperty;
use Ifksco\OpenApiGenerator\Objects\Json\Properties\OaEnumProperty;
use Ifksco\OpenApiGenerator\Objects\Json\Properties\OaObjectProperty;
use Ifksco\OpenApiGenerator\Objects\Json\Schemes\OaRequestScheme;
use Ifksco\OpenApiGenerator\Objects\Json\Schemes\OaScheme;
use Ifksco\OpenApiGenerator\Objects\PathObject;
use Ifksco\OpenApiGenerator\Sources\SourceInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * @see https://swagger.io/docs/specification/v3_0/basic-structure/
 */
class JsonFileGenerator
{
    private array $jsonData = [];

    public function __construct(private readonly SourceInterface $source)
    {
    }

    public function generate(bool $isPublic): void
    {
        $this->jsonData = [];

        $paths = [];

        /** @var array<class-string<BackedEnum>, OaEnumProperty> $enums */
        $enums = [];

        $hasSecurity = false;

        foreach ($this->source->getPaths() as $path) {
            $uri = '/'
                . trim($path->uri, '/');

            if (!array_key_exists($uri, $paths)) {
                $paths[$uri] = [];
            }

            $method = strtolower($path->methods[0]);

            $parameters = [];

            if ($path->summary) {
                $paths[$uri][$method]['summary'] = $path->summary;
            }

            if ($path->pathParameters) {
                foreach ($path->pathParameters as $parameter) {
                    $parameters[] = [
                        'name'     => trim($parameter, '?'),
                        'required' => !str_ends_with($parameter, '?'),
                        'in'       => 'path',
                        'schema'   => (object) [],
                    ];
                }
            }

            if ($path->requestScheme) {
                if (Str::upper($method) !== 'GET') {
                    $paths[$uri][$method]['requestBody'] = $this->defineRequestBody($path);
                } else {
                    if ($path->requestScheme->oneOf || $path->requestScheme->items) {
                        throw new RuntimeException(
                            "Route '$path->uri': this for GET operations not implemented yet"
                        );
                    }

                    foreach ($path->requestScheme->properties as $fieldName => $property) {
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

            $paths[$uri][$method]['responses'] = (object) $this->defineResponses($path, $isDeleteMethod);

            if ($isDeleteMethod && ($paths[$uri][$method]['requestBody'] ?? false)) {
                throw new RuntimeException(
                    'DELETE operations cannot have a requestBody.',
                );
            }

            foreach ($path->extensions as $extensionName => $extensionValue) {
                $paths[$uri][$method][$extensionName] = $extensionValue;
            }

            if ($path->isSecurity) {
                $hasSecurity = true;

                $paths[$uri][$method]['security'][] = [
                    'bearerAuth' => [],
                ];
            }

            $this->detectEnums($path->responses, $enums);
        }

        $schemas = [];

        /**
         * @var OaEnumProperty[] $enumProperties
         */
        $enumProperties = [
            ...array_values($enums),
            ...array_map(
                static function (string $enumClass): OaEnumProperty {
                    $enumProperty = new OaEnumProperty();

                    $enumProperty->enum = array_map(
                        fn(BackedEnum $backedEnum) => $backedEnum->value,
                        $enumClass::cases()
                    );

                    $enumProperty->enumClass = $enumClass;

                    return $enumProperty;
                },
                $this->source->getAdditionalEnums()
            ),
        ];

        foreach ($enumProperties as $enumProperty) {
            if (!$enumProperty->enumClass) {
                throw new RuntimeException(
                    "Enum class is not defined for enum property",
                );
            }

            $enumClassBasename = class_basename($enumProperty->enumClass);

            if (array_key_exists($enumClassBasename, $schemas)) {
                throw new RuntimeException("Enum '$enumClassBasename' already exists in the schema");
            }

            $schemas[$enumClassBasename] = [
                'type'            => $enumProperty->getEnumType()->value,
                'enum'            => $enumProperty->enum,
                'x-enum-varnames' => array_map(
                    fn(BackedEnum $backedEnum) => $backedEnum->name,
                    $enumProperty->enumClass::cases()
                ),
            ];
        }

        $description = $this->source->getDescription($isPublic);

        $this->jsonData = [
            'openapi'    => '3.0.0',
            'info'       => [
                'title'   => $this->source->getTitle(),
                'version' => $this->source->getVersion(),
                ...($description ? ['description' => $description] : []),
            ],
            'components' => (object) [
                ...(
                    $hasSecurity
                    ? [
                        'securitySchemes' => [
                            'bearerAuth' => [
                                'type'         => 'http',
                                'scheme'       => 'bearer',
                                'bearerFormat' => 'JWT',
                            ],
                        ],
                    ]
                    : []
                ),
                ...($schemas ? ['schemas' => $schemas] : []),
            ],
            'paths'      => (object) $paths,
        ];

        if ($isPublic) {
            $this->jsonData['servers'] = array_map(
                static function (ServerObject $server) {
                    $variables = [];

                    if ($server->variables) {
                        foreach ($server->variables as $variable) {
                            $variables[$variable->name] = [
                                'default' => $variable->default,
                                ...(
                                    $variable->description
                                    ? [
                                        'description' => $variable->description,
                                    ]
                                    : []
                                ),
                            ];
                        }
                    }

                    return [
                        'url' => $server->url,
                        ...(
                            $server->description
                            ? [
                                'description' => $server->description,
                            ]
                            : []
                        ),
                        ...(
                            count($variables)
                            ? [
                                'variables' => $variables,
                            ]
                            : []
                        ),
                    ];
                },
                $this->source->getServers()
            );
        }
    }

    public function saveToDisk(Filesystem $filesystem, string $fileName): void
    {
        $json = json_encode(
            $this->jsonData,
            JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
        );

        $filesystem->put("$fileName.json", $json);
    }

    private function defineRequestBody(PathObject $parsedRoute): array
    {
        $contentType = $parsedRoute->requestScheme?->contentType ?? OaRequestScheme::OA_CONTENT_TYPE_JSON;

        return [
            'required' => (bool) $parsedRoute->requestScheme,
            'content'  => [
                $contentType => [
                    'schema' => (object) ($parsedRoute->requestScheme?->toArray() ?? []),
                    ...(is_null($parsedRoute->requestExample) ? [] : ['example' => $parsedRoute->requestExample]),
                ],
            ],
        ];
    }

    /**
     * @param array<int, OaScheme|OaArrayProperty> $properties
     * @param OaEnumProperty[]                     $enums
     */
    private function detectEnums(array $properties, array &$enums): void
    {
        foreach ($properties as $schema) {
            if ($schema instanceof OaEnumProperty) {
                if (
                    !is_null($schema->enumClass)
                    && !array_key_exists($schema->enumClass, $enums)
                ) {
                    $enums[$schema->enumClass] = $schema;
                }

                continue;
            }

            if ($schema instanceof OaArrayProperty) {
                if (!is_null($schema->items)) {
                    $this->detectEnums(
                        [$schema->items],
                        $enums
                    );
                }

                continue;
            }

            if ($schema instanceof OaScheme) {
                $this->detectEnums($schema->oneOf, $enums);
                $this->detectEnums($schema->properties, $enums);
            }

            if ($schema instanceof OaObjectProperty && $schema->properties) {
                $this->detectEnums($schema->properties, $enums);
            }
        }
    }

    private function defineResponses(PathObject $parsedRoute, bool $isDeleteMethod): array
    {
        $responses = [];

        foreach ($parsedRoute->responses ?: [200 => null] as $statusCode => $schema) {
            if ($isDeleteMethod && $schema) {
                throw new RuntimeException(
                    'DELETE operations cannot have a responseBody.',
                );
            }

            $responses[$statusCode] = [
                'description' => 'description', // TODO: delete
            ];

            if (!$schema) {
                $schemaJson = (object) [];
            } else {
                $schemaJson = (object) $schema->toArray();
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
}
