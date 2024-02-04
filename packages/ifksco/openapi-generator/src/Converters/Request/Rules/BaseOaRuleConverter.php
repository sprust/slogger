<?php

namespace Ifksco\OpenApiGenerator\Converters\Request\Rules;

use Ifksco\OpenApiGenerator\Json\Properties\BaseOaProperty;

abstract class BaseOaRuleConverter
{
    abstract protected static function canHandleRule(mixed $rule): bool;

    abstract protected static function onDetectObject(array $ruleNames, mixed $rule): ?BaseOaProperty;

    abstract protected static function onFillProperty(BaseOaProperty $object, mixed $rule): void;

    public static function fill(BaseOaProperty $object, mixed $rule): bool
    {
        if (!static::canHandleRule($rule)) {
            return false;
        }

        static::onFillProperty($object, $rule);

        return true;
    }

    public static function detectObject(array $ruleNames, mixed $rule): ?BaseOaProperty
    {
        if (!static::canHandleRule($rule)) {
            return null;
        }

        return static::onDetectObject($ruleNames, $rule);
    }
}
