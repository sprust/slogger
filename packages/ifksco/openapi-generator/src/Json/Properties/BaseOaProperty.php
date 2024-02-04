<?php

namespace Ifksco\OpenApiGenerator\Json\Properties;

use Ifksco\OpenApiGenerator\Json\BaseOaJsonObject;

abstract class BaseOaProperty extends BaseOaJsonObject
{
    public ?string $format = null;

    public ?bool $required = null;
    public ?bool $nullable = null;
    public ?bool $readOnly = null;
    public ?bool $writeOnly = null;

    public array $requiredWithout = [];

    protected function basePropertiesToArray(): array
    {
        $data = [
            'type' => $this->getType()->value,
        ];

        $this->addToArrayIfNotNull($data, 'format', $this->format);
        $this->addToArrayIfNotNull($data, 'nullable', $this->nullable);
        $this->addToArrayIfNotNull($data, 'readOnly', $this->readOnly);
        $this->addToArrayIfNotNull($data, 'writeOnly', $this->writeOnly);

        $objectData = $this->propertiesToArray();

        return array_merge($data, $objectData);
    }
}
