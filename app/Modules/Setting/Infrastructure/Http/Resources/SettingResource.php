<?php

declare(strict_types=1);

namespace App\Modules\Setting\Infrastructure\Http\Resources;

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use App\Modules\Setting\Entities\SettingObject;

class SettingResource extends AbstractApiResource
{
    private string $key;
    private string $env_name;
    private string $description;
    private int $value;
    private int $default_value;
    private int $min;
    private int $max;
    private bool $is_overridden;
    private ?string $updated_at;

    public function __construct(SettingObject $setting)
    {
        parent::__construct($setting);

        $this->key           = $setting->definition->key->value;
        $this->env_name      = $setting->definition->envName;
        $this->description   = $setting->definition->description;
        $this->value         = $setting->value;
        $this->default_value = $setting->defaultValue;
        $this->min           = $setting->definition->min;
        $this->max           = $setting->definition->max;
        $this->is_overridden = $setting->isOverridden;
        $this->updated_at    = $setting->updatedAt?->toDateTimeString();
    }
}
