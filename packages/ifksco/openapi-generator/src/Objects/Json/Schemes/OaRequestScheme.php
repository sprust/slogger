<?php

namespace Ifksco\OpenApiGenerator\Objects\Json\Schemes;

use Ifksco\OpenApiGenerator\Objects\Json\BaseOaJsonObject;

class OaRequestScheme extends OaScheme
{
    public const OA_CONTENT_TYPE_JSON      = 'application/json';
    public const OA_CONTENT_TYPE_FORM_DATA = 'multipart/form-data';

    public string $contentType;

    /**
     * The scheme of an item when the body is a list, not an object.
     */
    public ?BaseOaJsonObject $items = null;

    protected function propertiesToArray(): array
    {
        if ($this->items) {
            return [
                'type'  => 'array',
                'items' => $this->items,
            ];
        }

        $data = parent::propertiesToArray();

        $data['type'] = 'object';

        return $data;
    }
}
