<?php

namespace Ifksco\OpenApiGenerator\Attributes;

use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;

class OaAttributeHelper
{
    public static function getRequestAttribute(
        ReflectionMethod|ReflectionProperty|ReflectionClass $reflection
    ): ?OaRequestAttribute {
        return self::getInstanceByReflection($reflection, OaRequestAttribute::class);
    }

    public static function getListItemTypeAttribute(
        ReflectionMethod|ReflectionProperty $reflection
    ): ?OaListItemTypeAttribute {
        return self::getInstanceByReflection($reflection, OaListItemTypeAttribute::class);
    }

    public static function getSummaryAttribute(
        ReflectionMethod|ReflectionProperty $reflection
    ): ?OaSummaryAttribute {
        return self::getInstanceByReflection($reflection, OaSummaryAttribute::class);
    }

    public static function getInstanceByReflection(
        ReflectionMethod|ReflectionProperty|ReflectionClass $reflection,
        string $attributeClass
    ): mixed {
        return ($reflection->getAttributes($attributeClass)[0] ?? null)?->newInstance();
    }
}
