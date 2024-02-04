<?php

namespace Ifksco\OpenApiGenerator\Converters\Request;

use App\Rules\Hotel\InnRule;
use Ifksco\OpenApiGenerator\Converters\Request\Rules\BaseOaRuleConverter;
use Ifksco\OpenApiGenerator\Converters\Request\Rules\OaRuleAsStringConverter;
use Ifksco\OpenApiGenerator\Json\Properties\BaseOaProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaArrayProperty;
use Ifksco\OpenApiGenerator\Json\Properties\OaObjectProperty;
use Ifksco\OpenApiGenerator\Json\Schemes\OaRequestScheme;
use Ifksco\OpenApiGenerator\Json\Schemes\OaScheme;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use RuntimeException;

use function explode;

class OaRulesParser
{
    /** @var array<BaseOaRuleConverter> */
    private array $converterClasses = [
        OaRuleAsStringConverter::class,
    ];

    private array $excludedForConvertingRules = [
        'exists',
        'bail',
        'prohibited_if',
        'prohibited',
        'required_if',
        'required_unless',
        'regex',
        InnRule::class,
    ];

    public function __construct(private readonly FormRequest $request, private readonly string $contentType)
    {
        $this->converterClasses           = array_merge(
            $this->converterClasses,
            config('oa-generator.custom.requests.converters')
        );
        $this->excludedForConvertingRules = array_merge(
            $this->excludedForConvertingRules,
            config('oa-generator.custom.requests.excluded_rules')
        );
    }

    public function getScheme(): OaRequestScheme
    {
        $objects     = [];
        $rules       = collect($this->request->rules());
        $nestedRules = $rules->filter(fn($value, $key) => Str::contains($key, ['.', '*']))->undot()->toArray();
        $scalarRules = $rules->filter(fn($value, $key) => !Str::contains($key, ['.', '*']))->toArray();

        foreach ($scalarRules as $ruleKey => $rules) {
            if (is_string($rules)) {
                $rules = explode('|', $rules);
            }

            if (count($nestedRules[$ruleKey] ?? [])) {
                $objects[$ruleKey] = $this->parseNested($nestedRules[$ruleKey]);
            } else {
                $objects[$ruleKey] = $this->parseRules($ruleKey, $rules);
            }
        }

        return $this->prepareScheme($objects);
    }

    private function parseNested(array $nestedRules): OaObjectProperty|OaArrayProperty
    {
        if (count($nestedRules) && !isset($nestedRules['*'])) {
            $parent  = new OaObjectProperty();
            $objects = [];
            foreach ($nestedRules as $ruleKey => $rules) {
                if (is_array($rules) && Arr::isAssoc($rules)) {
                    $nestedRules       = array_filter($rules, fn($item) => is_array($item));
                    $scalarObjectRules = array_filter($rules, fn($item) => !is_array($item));

                    $object = $this->parseNested($nestedRules);
                    $object = $this->fillObject($object, $scalarObjectRules, $ruleKey);

                    $objects[$ruleKey] = $object;
                } elseif ($ruleKey === '*') {
                    $objects[$ruleKey] = $this->parseNested(['*' => $rules]);
                } else {
                    $objects[$ruleKey] = $this->parseRules($ruleKey, $rules);
                }
            }

            $parent->properties = $objects;
        } else {
            $parent = new OaArrayProperty();

            $flattenCount = count(Arr::flatten($nestedRules['*']));
            $count        = count($nestedRules['*']);

            if ($flattenCount === $count) {
                $objectRules   = $this->parseRules('array', $nestedRules['*']);
                $parent->items = $objectRules;

                return $parent;
            }

            $object        = $this->parseNested($nestedRules['*']);
            $parent->items = $object;
        }

        return $parent;
    }

    private function parseRules(string $fieldName, array $rules): BaseOaProperty
    {
        $object = $this->detectObject($rules);

        if (!$object) {
            $this->throwException("Not detected type for field '$fieldName'");
        }

        return $this->fillObject($object, $rules, $fieldName);
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

    private function fillObject(BaseOaProperty $object, array $rules, string $fieldName): BaseOaProperty
    {
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
                $this->throwException("Not detected parser for field '$fieldName'", $rule);
            }
        }

        return $object;
    }

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

    private function throwException(string $message, $data = null)
    {
        throw new RuntimeException(
            'Request: ' . get_class($this->request) . '. ' . $message . ($data ? (PHP_EOL . print_r($data, true)) : '')
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
    private function prepareScheme(array $properties): OaRequestScheme
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
            ->filter(function (BaseOaProperty $property, string $fieldName) use ($requiredWithoutFieldNames) {
                return !in_array($fieldName, $requiredWithoutFieldNames);
            });

        $schema->properties = $filteredProperties->toArray();

        $schema->required = $filteredProperties
            ->filter(function (BaseOaProperty $property) {
                return $property->required;
            })
            ->keys()
            ->toArray();

        $schema->contentType = $this->contentType;

        return $schema;
    }
}
