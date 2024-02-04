<?php

namespace Ifksco\OpenApiGenerator\Json;

abstract class BaseOaJsonObject
{
    abstract protected function getType(): OaTypeEnum;

    abstract protected function basePropertiesToArray(): array;

    abstract protected function propertiesToArray(): array;

    final public function toArray(): array
    {
        $result = [];

        foreach (array_merge($this->basePropertiesToArray(), $this->propertiesToArray()) as $key => $value) {
            if ($this->isPropertyClass($value)) {
                $value = $value->toArray();
            } elseif (is_array($value)) {
                foreach ($value as $valueKey => $valueValue) {
                    $value[$valueKey] = $this->isPropertyClass($valueValue)
                        ? $valueValue->toArray()
                        : $valueValue;
                }
            }

            $result[$key] = $value;
        }

        return $result;
    }

    protected function addToArrayIfNotNull(array &$array, string $key, mixed $value)
    {
        if (is_null($value)) {
            return;
        }

        $array[$key] = $value;
    }

    private function isPropertyClass(mixed $property): bool
    {
        return is_object($property) && is_subclass_of($property, self::class);
    }
}
