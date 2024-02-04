<?php

namespace Ifksco\OpenApiGenerator\Json\Properties;

use Ifksco\OpenApiGenerator\Json\OaTypeEnum;

class OaBooleanProperty extends BaseOaProperty
{
    protected function getType(): OaTypeEnum
    {
        return OaTypeEnum::Boolean;
    }

    protected function propertiesToArray(): array
    {
        return [];
    }
}
