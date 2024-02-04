<?php

namespace Ifksco\OpenApiGenerator\Json\Properties;

class OaPaginationInfoProperty extends OaObjectProperty
{
    public function __construct()
    {
        $this->properties = [
            'current_page' => new OaNumberProperty(),
            'last_page'    => new OaNumberProperty(),
            'total'        => new OaNumberProperty(),
        ];

        $this->requiredProperties = [
            'current_page',
            'last_page',
            'total',
        ];
    }

    protected function propertiesToArray(): array
    {
        $data = [];

        $this->addToArrayIfNotNull($data, 'properties', $this->properties);
        $this->addToArrayIfNotNull($data, 'required', $this->requiredProperties);

        return $data;
    }
}
