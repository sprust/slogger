<?php

namespace Ifksco\OpenApiGenerator\Json\Schemes;

class OaRequestScheme extends OaScheme
{
    public const OA_CONTENT_TYPE_JSON      = 'application/json';
    public const OA_CONTENT_TYPE_FORM_DATA = 'multipart/form-data';

    public string $contentType;

    protected function propertiesToArray(): array
    {
        $data = parent::propertiesToArray();

        $data['type'] = 'object';

        return $data;
    }
}
