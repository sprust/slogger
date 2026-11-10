<?php

namespace Ifksco\OpenApiGenerator\Objects\Json\Properties;

use Ifksco\OpenApiGenerator\Objects\Json\OaTypeEnum;

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
