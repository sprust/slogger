<?php

namespace Ifksco\OpenApiGenerator\Objects\Json\Properties;

use Ifksco\OpenApiGenerator\Objects\Json\BaseOaJsonObject;

abstract class BaseOaProperty extends BaseOaJsonObject
{
    public ?string $format = null;

    public ?bool $required  = null;
    public ?bool $nullable  = null;
    public ?bool $readOnly  = null;
    public ?bool $writeOnly = null;

    public array $requiredWithout = [];

    protected function basePropertiesToArray(): array
    {
        $objectData = $this->propertiesToArray();

        if (array_key_exists('$ref', $objectData)) {
            return [
                '$ref' => $objectData['$ref'],
            ];
        }

        $data = [];

        if (!($this instanceof OaEnumProperty)) {
            $data['type'] = $this->getType()->value;
        }

        $this->addToArrayIfNotNull($data, 'format', $this->format);
        $this->addToArrayIfNotNull($data, 'nullable', $this->nullable);
        $this->addToArrayIfNotNull($data, 'readOnly', $this->readOnly);
        $this->addToArrayIfNotNull($data, 'writeOnly', $this->writeOnly);

        return array_merge($data, $objectData);
    }
}
