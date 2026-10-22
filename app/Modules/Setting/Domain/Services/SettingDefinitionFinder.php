<?php

declare(strict_types=1);

namespace App\Modules\Setting\Domain\Services;

use App\Modules\Setting\Entities\SettingDefinitionObject;
use App\Modules\Setting\Enums\SettingKeyEnum;

readonly class SettingDefinitionFinder
{
    /**
     * @return SettingDefinitionObject[]
     */
    public function findAll(): array
    {
        return array_map(
            fn(SettingKeyEnum $key): SettingDefinitionObject => $this->find($key),
            SettingKeyEnum::cases()
        );
    }

    public function find(SettingKeyEnum $key): SettingDefinitionObject
    {
        return match ($key) {
            SettingKeyEnum::TracesLifetimeHours => new SettingDefinitionObject(
                key: $key,
                envName: 'TRACES_LIFETIME_HOURS',
                configKey: 'cleaner.lifetime_hours',
                min: 1,
                max: 8760,
                description: 'How many hours traces are kept. It is also the longest period MCP tools may search.'
            ),
            SettingKeyEnum::LogsNginxKeepDays => new SettingDefinitionObject(
                key: $key,
                envName: 'LOGS_NGINX_KEEP_DAYS',
                configKey: 'module-logs.nginx_keep_days',
                min: 1,
                max: 3650,
                description: 'How many days nginx access logs are kept.'
            ),
        };
    }
}
