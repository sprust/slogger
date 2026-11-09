<?php

namespace Ifksco\OpenApiGenerator\Sources\Illuminate\Converters\Request;

use Ifksco\OpenApiGenerator\Objects\Json\Properties\BaseOaProperty;
use Ifksco\OpenApiGenerator\Objects\Json\Properties\OaArrayProperty;
use Ifksco\OpenApiGenerator\Objects\Json\Properties\OaObjectProperty;
use Ifksco\OpenApiGenerator\Objects\Json\Schemes\OaRequestScheme;
use Ifksco\OpenApiGenerator\Objects\Json\Schemes\OaScheme;
use Ifksco\OpenApiGenerator\Sources\Illuminate\Converters\Request\Rules\BaseOaRuleConverter;
use Ifksco\OpenApiGenerator\Sources\Illuminate\Converters\Request\Rules\OaRuleAsStringConverter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

class OaRulesParser
{
    /**
     * @var class-string<BaseOaRuleConverter>[]
     */
    private array $converterClasses = [
        OaRuleAsStringConverter::class,
    ];

    /**
     * @var string[]
     */
    private array $excludedForConvertingRules = [
        'exists',
        'bail',
        'prohibited_if',
        'prohibited_if_accepted',
        'prohibited',
        'required_if',
        'required_unless',
        'regex',
    ];

    public function __construct()
    {
        $this->converterClasses = array_merge(
            $this->converterClasses,
            config('oa-generator.custom.requests.converters')
        );

        $this->excludedForConvertingRules = array_merge(
            $this->excludedForConvertingRules,
            config('oa-generator.custom.requests.excluded_rules')
        );
    }

    public function makeSchemeByRequest(FormRequest $request, string $contentType): OaRequestScheme
    {
        $subject = $request::class;

        return $this->makeSchemeByRules(
            subject: $subject,
            rules: $request->rules(),
            contentType: $contentType
        );
    }

    /**
     * @param array<string, array> $rules
     */
    public function makeSchemeByRules(string $subject, array $rules, string $contentType): OaRequestScheme
    {
        $rulesCollection = collect($rules);

        $nestedRules = $rulesCollection
            ->filter(static fn($value, $key) => Str::contains($key, ['.', '*']))
            ->undot()
            ->toArray();

        $scalarRules = $rulesCollection
            ->filter(static fn($value, $key) => !Str::contains($key, ['.', '*']))
            ->toArray();

        if (array_key_exists('*', $nestedRules)) {
            if (count($nestedRules) > 1 || count($scalarRules) > 0) {
                $this->throwException(
                    subject: $subject,
                    message: 'A list body can not have named root fields'
                );
            }

            $schema = new OaRequestScheme();

            $schema->items       = $this->parseListItem(subject: $subject, rules: $nestedRules['*']);
            $schema->contentType = $contentType;

            return $schema;
        }

        $objects = [];

        foreach ($scalarRules as $ruleKey => $rules) {
            if (is_string($rules)) {
                $rules = explode('|', $rules);
            }

            if (count($nestedRules[$ruleKey] ?? [])) {
                $objects[$ruleKey] = $this->parseNested(
                    subject: $subject,
                    nestedRules: $nestedRules[$ruleKey]
                );
            } else {
                $objects[$ruleKey] = $this->parseRules(
                    subject: $subject,
                    fieldName: $ruleKey,
                    rules: $rules
                );
            }

            if (in_array('required', $rules)) {
                $objects[$ruleKey]->required = true;
            }

            if (in_array('nullable', $rules)) {
                $objects[$ruleKey]->nullable = true;
            }
        }

        return $this->prepareScheme($contentType, $objects);
    }

    private function parseNested(string $subject, array $nestedRules): OaObjectProperty|OaArrayProperty
    {
        if (count($nestedRules) && !isset($nestedRules['*'])) {
            $parent = new OaObjectProperty();

            $objects = [];

            foreach ($nestedRules as $ruleKey => $rules) {
                if (is_array($rules) && Arr::isAssoc($rules)) {
                    $nestedRules       = array_filter($rules, static fn($item) => is_array($item));
                    $scalarObjectRules = array_filter($rules, static fn($item) => !is_array($item));

                    $object = $this->parseNested(
                        subject: $subject,
                        nestedRules: $nestedRules
                    );

                    $object = $this->fillObject(
                        subject: $subject,
                        object: $object,
                        rules: $scalarObjectRules,
                        fieldName: $ruleKey
                    );

                    $objects[$ruleKey] = $object;
                } elseif ($ruleKey === '*') {
                    $objects[$ruleKey] = $this->parseNested(
                        subject: $subject,
                        nestedRules: ['*' => $rules]
                    );
                } else {
                    $objects[$ruleKey] = $this->parseRules(
                        subject: $subject,
                        fieldName: $ruleKey,
                        rules: $rules
                    );
                }
            }

            $parent->properties = $objects;

            $requiredProperties = collect($objects)
                ->filter(static fn(BaseOaProperty $property) => $property->required)
                ->keys()
                ->toArray();

            $parent->requiredProperties = $requiredProperties ?: null;
        } else {
            $parent = new OaArrayProperty();

            $parent->items = $this->parseListItem(
                subject: $subject,
                rules: $nestedRules['*'],
            );
        }

        return $parent;
    }

    /**
     * The rules of an item come mixed after undot: own rules under int keys, rules of the fields under their names.
     */
    private function parseListItem(string $subject, array|string $rules): BaseOaProperty
    {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }

        $fieldRules = array_filter($rules, static fn($item) => is_array($item));
        $ownRules   = array_filter($rules, static fn($item) => !is_array($item));

        if (count($fieldRules) === 0) {
            return $this->parseRules(
                subject: $subject,
                fieldName: '*',
                rules: $ownRules
            );
        }

        return $this->fillObject(
            subject: $subject,
            object: $this->parseNested(
                subject: $subject,
                nestedRules: $fieldRules
            ),
            rules: $ownRules,
            fieldName: '*'
        );
    }

    private function parseRules(string $subject, string $fieldName, array $rules): BaseOaProperty
    {
        $object = $this->detectObject($rules);

        if (!$object) {
            $this->throwException(
                subject: $subject,
                message: "Not detected type for field '$fieldName'"
            );
        }

        return $this->fillObject(
            subject: $subject,
            object: $object,
            rules: $rules,
            fieldName: $fieldName
        );
    }

    private function detectObject(array $rules): ?BaseOaProperty
    {
        $object = null;

        $ruleNames = $this->getRuleNames($rules);

        foreach ($rules as $rule) {
            foreach ($this->converterClasses as $converterClass) {
                $object = $converterClass::detectObject($ruleNames, $rule);

                if ($object) {
                    break;
                }
            }

            if ($object) {
                break;
            }
        }

        return $object;
    }

    private function fillObject(
        string $subject,
        BaseOaProperty $object,
        array $rules,
        string $fieldName
    ): BaseOaProperty {
        foreach ($rules as $rule) {
            if ($this->isExcludedForConvertingRule($rule)) {
                continue;
            }

            $filled = false;

            foreach ($this->converterClasses as $converterClass) {
                $filled = $converterClass::fill($object, $rule);

                if ($filled) {
                    break;
                }
            }

            if (!$filled) {
                $this->throwException(
                    $subject,
                    "Not detected parser for field '$fieldName'",
                    $rule
                );
            }
        }

        return $object;
    }

    /**
     * @param string[] $rules
     */
    private function getRuleNames(array $rules): array
    {
        $ruleNames = [];

        foreach ($rules as $rule) {
            if (!is_string($rule)) {
                continue;
            }

            $ruleNames[] = explode(':', $rule)[0];
        }

        return $ruleNames;
    }

    private function throwException(string $subject, string $message, $data = null): never
    {
        throw new RuntimeException(
            'Subject: ' . $subject . '. ' . $message . ($data ? (PHP_EOL . print_r($data, true)) : '')
        );
    }

    private function isExcludedForConvertingRule(mixed $rule): bool
    {
        if (is_callable($rule)) {
            return true;
        }

        $ruleName = is_object($rule)
            ? get_class($rule)
            : explode(':', $rule)[0];

        return in_array($ruleName, $this->excludedForConvertingRules);
    }

    /**
     * @param array<string, BaseOaProperty> $properties
     */
    private function prepareScheme(string $contentType, array $properties): OaRequestScheme
    {
        $oneOf = [];

        $requiredWithoutFieldNames = [];

        foreach ($properties as $fieldName => $object) {
            if (!$object->requiredWithout) {
                continue;
            }

            $requiredWithoutFieldNames[] = $fieldName;

            $schema                         = new OaScheme();
            $schema->properties[$fieldName] = $object;
            $schema->required               = $object->requiredWithout;

            $oneOf[] = $schema;
        }

        $schema = new OaRequestScheme();

        if ($oneOf) {
            $schema->oneOf = $oneOf;
        }

        $filteredProperties = collect($properties)
            ->filter(
                static function (BaseOaProperty $property, string $fieldName) use ($requiredWithoutFieldNames) {
                    return !in_array($fieldName, $requiredWithoutFieldNames);
                }
            );

        $schema->properties = $filteredProperties->toArray();

        $schema->required = $filteredProperties
            ->filter(static function (BaseOaProperty $property) {
                return $property->required;
            })
            ->keys()
            ->toArray();

        $schema->contentType = $contentType;

        return $schema;
    }
}
