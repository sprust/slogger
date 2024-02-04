<?php

namespace Ifksco\OpenApiGenerator\Converters\Request\Rules;

use Ifksco\OpenApiGenerator\Json\Properties\BaseOaProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaArrayProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaBooleanProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaIntegerProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaNumberProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaStringProperty;
use Illuminate\Support\Str;
use RuntimeException;

class OaRuleAsStringConverter extends BaseOaRuleConverter
{
    private static ?array $excludedForFillRules = null;

    private static function getExcludedForFillRules(): array
    {
        return self::$excludedForFillRules
            ?:
            array_merge(
                [
                    'string',
                    'integer',
                    'number',
                    'int',
                    'numeric',
                    'array',
                    'mimetypes',
                    'dimensions',
                    'unique',
                    'exists',
                    'ipv4',
                    'ipv6',
                    'gt',
                    'different',
                    'prohibited_if',
                    'after',
                    'decimal',
                    'distinct',
                    'date_format',
                    'after_or_equal',
                    'before_or_equal',
                    'uuid',
                    'ip',
                    'size',
                    'exclude_if',
                    'current_password',
                ],
                config('oa-generator.custom.requests.excluded_for_fill_rule_as_strings')
            );
    }

    protected static function canHandleRule(mixed $rule): bool
    {
        return is_string($rule);
    }

    public static function onDetectObject(array $ruleNames, mixed $rule): ?BaseOaProperty
    {
        $isFile     = in_array('file', $ruleNames);
        $isEmail    = in_array('email', $ruleNames);
        $isPassword = in_array('password', $ruleNames);
        $isIpv4     = in_array('ipv4', $ruleNames);
        $isIpv6     = in_array('ipv6', $ruleNames);
        $isDate     = in_array('date', $ruleNames);
        $isMimetype = in_array('mimetypes', $ruleNames);
        $isUuid     = in_array('uuid', $ruleNames);

        if ($isFile
            || $isEmail
            || $isPassword
            || $isIpv4
            || $isIpv6
            || $isDate
            || $isMimetype
            || in_array('string', $ruleNames)
        ) {
            $object = new OaStringProperty();

            if ($isFile) {
                $object->format = OaStringProperty::FORMAT_BINARY;
            } elseif ($isEmail) {
                $object->format = OaStringProperty::FORMAT_EMAIL;
            } elseif ($isPassword) {
                $object->format = OaStringProperty::FORMAT_PASSWORD;
            } elseif ($isIpv4) {
                $object->format = OaStringProperty::FORMAT_IPV4;
            } elseif ($isIpv6) {
                $object->format = OaStringProperty::FORMAT_IPV6;
            } elseif ($isDate) {
                $object->format = OaStringProperty::FORMAT_DATE;
            } elseif ($isMimetype) {
                $object->format = OaStringProperty::FORMAT_BINARY;
            } elseif ($isUuid) {
                $object->format = OaStringProperty::FORMAT_UUID;
            }

            return $object;
        }

        if (in_array('int', $ruleNames) || in_array('integer', $ruleNames)) {
            return new OaIntegerProperty();
        }

        if (in_array('numeric', $ruleNames)) {
            $object         = new OaNumberProperty();
            $object->format = OaNumberProperty::FORMAT_FLOAT;

            return $object;
        }

        if (in_array('array', $ruleNames)) {
            return new OaArrayProperty();
        }

        if (in_array('bool', $ruleNames) || in_array('boolean', $ruleNames)) {
            return new OaBooleanProperty();
        }

        return null;
    }

    protected static function onFillProperty(BaseOaProperty $object, mixed $rule): void
    {
        $ruleData = explode(':', $rule);

        $ruleName = strtolower($ruleData[0]);

        if (in_array($ruleName, self::getExcludedForFillRules())) {
            return;
        }

        $parameters = $ruleData[1] ?? null;

        if ($ruleName === 'required') {
            $object->required = true;

            return;
        }

        if ($ruleName === 'sometimes') {
            $object->required = false;

            return;
        }

        if ($ruleName === 'nullable') {
            $object->nullable = true;

            return;
        }

        if ($ruleName === 'email') {
            $object->format = OaStringProperty::FORMAT_EMAIL;

            return;
        }

        if ($ruleName === 'min') {
            switch (get_class($object)) {
                case OaStringProperty::class:
                    $object->minLength = $parameters;
                    break;
                case OaNumberProperty::class:
                case OaIntegerProperty::class:
                    $object->minimum = $parameters;
                    break;
                case OaArrayProperty::class:
                    $object->minItems = $parameters;
                    break;
                default:
                    throw new RuntimeException(
                        "Object '" . get_class($object) . "' can't to keep '$ruleName' rule"
                    );
            }

            return;
        }

        if ($ruleName === 'max') {
            switch (get_class($object)) {
                case OaStringProperty::class:
                    $object->maxLength = $parameters;
                    break;
                case OaNumberProperty::class:
                case OaIntegerProperty::class:
                    $object->maximum = $parameters;
                    break;
                case OaArrayProperty::class:
                    $object->maxItems = $parameters;
                    break;
                default:
                    throw new RuntimeException(
                        "Object '" . get_class($object) . "' can't to keep '$ruleName' rule"
                    );
            }

            return;
        }

        if ($ruleName === 'numeric') {
            switch (get_class($object)) {
                case OaNumberProperty::class:
                    break;
                default:
                    throw new RuntimeException(
                        "Object '" . get_class($object) . "' can't to keep '$ruleName' rule"
                    );
            }

            return;
        }

        if ($ruleName === 'digits') {
            switch (get_class($object)) {
                case OaNumberProperty::class:
                case OaIntegerProperty::class:
                    $object->minimum = Str::of('1')->padRight($parameters, '0')->toInteger();
                    $object->maximum = Str::of('')->padRight($parameters, '9')->toInteger();
                    break;
                default:
                    throw new RuntimeException(
                        "Object '" . get_class($object) . "' can't to keep '$ruleName' rule"
                    );
            }

            return;
        }

        if ($ruleName === 'gt') {
            $object->greater = match (get_class($object)) {
                OaNumberProperty::class, OaIntegerProperty::class => $parameters,
                default => throw new RuntimeException(
                    "Object '" . get_class($object) . "' can't to keep '$ruleName' rule"
                ),
            };

            return;
        }

        if ($ruleName === 'present') {
            $object->required = false;
            $object->nullable = false;

            return;
        }

        if (in_array($ruleName, ['required_without', 'required_with', 'required_without_all'])) {
            $object->requiredWithout = explode(',', $parameters);

            return;
        }

        if ($ruleName === 'bool' || $ruleName === 'boolean') {
            return;
        }

        if ($ruleName === 'in') {
            $object->enum = match (get_class($object)) {
                OaStringProperty::class, OaIntegerProperty::class => explode(',', $parameters),
                default => throw new RuntimeException(
                    "Object '" . get_class($object) . "' can't keep '$ruleName' rule"
                ),
            };

            return;
        }

        throw new RuntimeException("Not implemented rule: " . $rule);
    }
}
