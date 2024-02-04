<?php

namespace Ifksco\OpenApiGenerator\Converters\Responses;

use Ifksco\OpenApiGenerator\Attributes\OaAttributeHelper;
use Ifksco\OpenApiGenerator\Attributes\OaListItemTypeAttribute;
use Ifksco\OpenApiGenerator\Json\BaseOaJsonObject;
use Ifksco\OpenApiGenerator\Json\Properties\BaseOaProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaArrayProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaBooleanProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaEnumProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaIntegerProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaNumberProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaStringProperty;
use Ifksco\OpenApiGenerator\Json\Schemes\OaScheme;
use Ifksco\OpenApiGenerator\OaService;
use Illuminate\Http\Resources\Json\JsonResource;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionType;
use RuntimeException;

class OaResponsesParser
{
    private int $allowedRecursionDepth = 5;

    /**
     * @param class-string<JsonResource> $resourceClass
     * @param CustomResponseProperty[]   $customProperties
     */
    public function __construct(
        private readonly string $resourceClass,
        private readonly array $customProperties = [],
        private readonly int $recursiveDepth = 0
    ) {
    }

    /**
     * @throws ReflectionException
     */
    public function getScheme(): BaseOaJsonObject
    {
        $resourceReflection = new ReflectionClass($this->resourceClass);

        $scheme = new OaScheme();

        $customProperties = collect($this->customProperties)
            ->keyBy(
                fn(CustomResponseProperty $customProperty) => $customProperty->name
            )
            ->toArray();

        foreach ($resourceReflection->getProperties() as $reflectionProperty) {
            if (!$reflectionProperty->isPrivate()
                || $reflectionProperty->getDeclaringClass()->getName() != $this->resourceClass
            ) {
                continue;
            }

            $propertyName = $reflectionProperty->getName();

            /** @var CustomResponseProperty|null $customProperty */
            if ($customProperty = $customProperties[$propertyName] ?? null) {
                $scheme->properties[$propertyName] = $this->parseCustomProperty($customProperty);
            } else {
                $propertyType = $reflectionProperty->getType();

                if (method_exists($propertyType, 'getTypes')) {
                    $propertyType = $propertyType->getTypes()[0];
                }

                /** @var ReflectionType|ReflectionNamedType $propertyType */

                $scheme->properties[$propertyName] = $this->parseReflectionPropertyDTO(
                    new ReflectionPropertyDto(
                        name: $propertyName,
                        type: $propertyType->getName(),
                        declaringClass: $reflectionProperty->getDeclaringClass()->getName(),
                        nullable: $propertyType->allowsNull(),
                        listItemAttribute: OaAttributeHelper::getListItemTypeAttribute($reflectionProperty)
                    )
                );
            }
        }

        if (!$scheme->properties) {
            throw new RuntimeException('Not found private properties for ' . $this->resourceClass);
        }

        foreach ($scheme->properties as $fieldName => $property) {
            if (
                $property instanceof BaseOaProperty && $property->required
                || $property instanceof OaScheme && !($property->nullable ?? false)
            ) {
                $scheme->required[] = $fieldName;
            }
        }

        return $scheme;
    }

    private function parseCustomProperty(CustomResponseProperty $customProperty): BaseOaProperty|OaScheme
    {
        if (OaService::isClassResource($customProperty->type)) {
            return $this->parseReflectionPropertyDTO(
                new ReflectionPropertyDto(
                    name: $customProperty->name,
                    type: $customProperty->type,
                    declaringClass: $customProperty->type,
                    nullable: $customProperty->nullable,
                    listItemAttribute: null
                )
            );
        }

        if (is_array($customProperty->type)) {
            $property       = new OaEnumProperty();
            $property->enum = $customProperty->type;

            return $property;
        }

        throw new RuntimeException('Unknown custom type ' . PHP_EOL . print_r($customProperty->type, true));
    }

    private function parseReflectionPropertyDTO(ReflectionPropertyDto $reflectionPropertyDTO): BaseOaProperty|OaScheme
    {
        $propertyTypeName = $reflectionPropertyDTO->type;

        if ($propertyTypeName !== 'array') {
            $property = $this->detectJsonProperty($propertyTypeName);
        } else {
            $property = new OaArrayProperty();

            if ($this->recursiveDepth < $this->allowedRecursionDepth) {
                $attribute = $reflectionPropertyDTO->listItemAttribute;

                if (!$attribute) {
                    $propertyView = $reflectionPropertyDTO->declaringClass . '::' . $reflectionPropertyDTO->name;

                    throw new RuntimeException(
                        "Method: $propertyView" . PHP_EOL .
                        "Not found attribute: " . class_basename(OaListItemTypeAttribute::class)
                    );
                }

                $isRecursiveArray = $attribute->isRecursive();
                $className        = trim($attribute->getClassName(), '\\');

                $property->items = $this->detectJsonProperty($className, $isRecursiveArray);
            } else {
                $property->items    = new OaStringProperty();
                $property->maxItems = 0;
            }
        }

        $property->nullable = $reflectionPropertyDTO->nullable;

        if (!$property instanceof OaScheme) {
            $property->required = !$property->nullable;
        }

        return $property;
    }

    private function detectJsonProperty(string $propertyTypeName, bool $isRecursiveArray = false): BaseOaJsonObject
    {
        if ($propertyTypeName === 'string') {
            return new OaStringProperty();
        }

        if ($propertyTypeName === 'int') {
            return new OaIntegerProperty();
        }

        if ($propertyTypeName === 'float') {
            return new OaNumberProperty();
        }

        if ($propertyTypeName === 'bool') {
            return new OaBooleanProperty();
        }

        if (class_exists($propertyTypeName)) {
            if ((new ReflectionClass($propertyTypeName))->isEnum()) {
                $property = new OaEnumProperty();

                $property->enum = array_map(
                    fn(\BackedEnum $enum) => $enum->value,
                    $propertyTypeName::cases()
                );

                return $property;
            }

            if (OaService::isClassResource($propertyTypeName)) {
                $depth = $this->recursiveDepth;
                if ($isRecursiveArray) {
                    ++$depth;
                }

                return (new self($propertyTypeName, recursiveDepth: $depth))->getScheme();
            }
        }


        throw new RuntimeException('Type of field not detected for type ' . $propertyTypeName);
    }
}
