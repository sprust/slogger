<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Infrastructure\Tools\Contracts;

use stdClass;

readonly class McpToolSchemaCompiler
{
    /**
     * @return array<string, mixed>
     */
    public function toJsonSchema(McpToolSchema $schema): array
    {
        $properties = new stdClass();
        $required   = [];

        foreach ($schema->properties as $property) {
            $properties->{$property->name} = $this->propertyToJsonSchema($property);

            if ($property->required) {
                $required[] = $property->name;
            }
        }

        $jsonSchema = [
            'type'                 => 'object',
            'properties'           => $properties,
            'additionalProperties' => false,
        ];

        if (count($required) > 0) {
            $jsonSchema['required'] = $required;
        }

        return $jsonSchema;
    }

    /**
     * @return array<string, string[]>
     */
    public function toRules(McpToolSchema $schema): array
    {
        $rules = [];

        foreach ($schema->properties as $property) {
            $presence = $property->required ? 'required' : 'sometimes';

            switch ($property->type) {
                case McpToolPropertyTypeEnum::String:
                    $rules[$property->name] = [
                        $presence,
                        'string',
                        ...$this->boundRules($property, minDefault: 1),
                        ...$this->enumRules($property),
                    ];
                    break;
                case McpToolPropertyTypeEnum::Integer:
                    $rules[$property->name] = [$presence, 'integer', ...$this->boundRules($property)];
                    break;
                case McpToolPropertyTypeEnum::Number:
                    $rules[$property->name] = [$presence, 'numeric', ...$this->boundRules($property)];
                    break;
                case McpToolPropertyTypeEnum::Boolean:
                    $rules[$property->name] = [$presence, 'boolean'];
                    break;
                case McpToolPropertyTypeEnum::StringList:
                    $rules[$property->name]     = [$presence, 'array', 'list', ...$this->boundRules($property)];
                    $rules["$property->name.*"] = ['required', 'string', 'min:1', ...$this->enumRules($property)];
                    break;
                case McpToolPropertyTypeEnum::IntegerList:
                    $rules[$property->name]     = [$presence, 'array', 'list', ...$this->boundRules($property)];
                    $rules["$property->name.*"] = ['required', 'integer'];
                    break;
            }
        }

        return $rules;
    }

    /**
     * @return array<string, mixed>
     */
    private function propertyToJsonSchema(McpToolProperty $property): array
    {
        $isList = in_array(
            $property->type,
            [McpToolPropertyTypeEnum::StringList, McpToolPropertyTypeEnum::IntegerList],
            true
        );

        if (!$isList) {
            $schema = [
                'type'        => $property->type->value,
                'description' => $property->description,
            ];

            if (!is_null($property->enum)) {
                $schema['enum'] = $property->enum;
            }

            return [...$schema, ...$this->bounds($property, $property->type === McpToolPropertyTypeEnum::String)];
        }

        $items = [
            'type' => $property->type === McpToolPropertyTypeEnum::StringList ? 'string' : 'integer',
        ];

        if (!is_null($property->enum)) {
            $items['enum'] = $property->enum;
        }

        $schema = [
            'type'        => 'array',
            'description' => $property->description,
            'items'       => $items,
        ];

        if (!is_null($property->min)) {
            $schema['minItems'] = $property->min;
        }

        if (!is_null($property->max)) {
            $schema['maxItems'] = $property->max;
        }

        return $schema;
    }

    /**
     * @return array<string, int>
     */
    private function bounds(McpToolProperty $property, bool $isString): array
    {
        $bounds = [];
        $min    = $property->min ?? ($isString ? 1 : null);

        if (!is_null($min)) {
            $bounds[$isString ? 'minLength' : 'minimum'] = $min;
        }

        if (!is_null($property->max)) {
            $bounds[$isString ? 'maxLength' : 'maximum'] = $property->max;
        }

        return $bounds;
    }

    /**
     * @return string[]
     */
    private function boundRules(McpToolProperty $property, ?int $minDefault = null): array
    {
        $rules = [];
        $min   = $property->min ?? $minDefault;

        if (!is_null($min)) {
            $rules[] = "min:$min";
        }

        if (!is_null($property->max)) {
            $rules[] = "max:$property->max";
        }

        return $rules;
    }

    /**
     * @return string[]
     */
    private function enumRules(McpToolProperty $property): array
    {
        return is_null($property->enum) ? [] : ['in:' . implode(',', $property->enum)];
    }
}
