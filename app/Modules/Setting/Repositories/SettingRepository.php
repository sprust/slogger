<?php

declare(strict_types=1);

namespace App\Modules\Setting\Repositories;

use App\Models\Settings\Setting;
use App\Modules\Setting\Entities\SettingValueObject;
use App\Modules\Setting\Enums\SettingKeyEnum;

class SettingRepository
{
    /**
     * @return SettingValueObject[]
     */
    public function find(): array
    {
        $objects = [];

        foreach (Setting::query()->get() as $setting) {
            $object = $this->makeObjectByModel($setting);

            if (!is_null($object)) {
                $objects[] = $object;
            }
        }

        return $objects;
    }

    public function findOne(SettingKeyEnum $key): ?SettingValueObject
    {
        $setting = Setting::query()->where('key', $key->value)->first();

        return $setting ? $this->makeObjectByModel($setting) : null;
    }

    public function set(SettingKeyEnum $key, int $value): void
    {
        $setting = Setting::query()->where('key', $key->value)->first() ?? new Setting();

        $setting->key   = $key->value;
        $setting->value = (string) $value;

        $setting->saveOrFail();
    }

    public function delete(SettingKeyEnum $key): void
    {
        Setting::query()->where('key', $key->value)->delete();
    }

    private function makeObjectByModel(Setting $setting): ?SettingValueObject
    {
        $key = SettingKeyEnum::tryFrom($setting->key);

        $value = filter_var($setting->value, FILTER_VALIDATE_INT);

        if (is_null($key) || !is_int($value)) {
            return null;
        }

        return new SettingValueObject(
            key: $key,
            value: $value,
            updatedAt: $setting->updated_at
        );
    }
}
