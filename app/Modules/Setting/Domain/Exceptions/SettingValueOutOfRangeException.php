<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\Exceptions;

use App\Modules\Setting\Entities\SettingDefinitionObject;
use Exception;

class SettingValueOutOfRangeException extends Exception
{
    public function __construct(SettingDefinitionObject $definition, int $value)
    {
        parent::__construct(
            sprintf('%s must be from %d to %d, got %d', $definition->envName, $definition->min, $definition->max, $value)
        );
    }
}
