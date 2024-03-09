<?php

namespace App\Modules\TraceCleaner\Repositories;

use App\Models\Traces\TraceClearingSetting;
use App\Modules\TraceCleaner\Repositories\Contracts\SettingRepositoryInterface;
use App\Modules\TraceCleaner\Repositories\Dto\SettingDto;
use Illuminate\Database\Eloquent\Builder;

class SettingRepository implements SettingRepositoryInterface
{
    public function find(?string $type = null, ?bool $typeIsNotNull = null): array
    {
        return TraceClearingSetting::query()
            ->when(
                !is_null($type),
                fn(Builder $query) => $query->where('type', $type)
            )
            ->when(
                is_null($type) && !is_null($typeIsNotNull),
                fn(Builder $query) => $typeIsNotNull
                    ? $query->whereNotNull('type')
                    : $query->whereNull('type')
            )
            ->get()
            ->map(
                fn(TraceClearingSetting $setting) => new SettingDto(
                    id: $setting->id,
                    daysLifetime: $setting->days_lifetime,
                    type: $setting->type,
                    createdAt: $setting->created_at,
                    updatedAt: $setting->updated_at
                ),
            )
            ->toArray();
    }

    public function findOneById(int $id): ?SettingDto
    {
        /** @var TraceClearingSetting|null $setting */
        $setting = TraceClearingSetting::query()->find($id);

        if (!$setting) {
            return null;
        }

        return new SettingDto(
            id: $setting->id,
            daysLifetime: $setting->days_lifetime,
            type: $setting->type,
            createdAt: $setting->created_at,
            updatedAt: $setting->updated_at
        );
    }

    public function create(int $daysLifetime, ?string $type): int
    {
        $newSetting = new TraceClearingSetting();

        $newSetting->days_lifetime = $daysLifetime;
        $newSetting->type          = $type;

        $newSetting->saveOrFail();

        return $newSetting->id;
    }

    public function update(int $id, int $daysLifetime, ?string $type): bool
    {
        return (bool) TraceClearingSetting::query()
            ->where('id', $id)
            ->update([
                'days_lifetime' => $daysLifetime,
                'type'          => $type,
            ]);
    }

    public function delete(int $id): void
    {
        TraceClearingSetting::query()->where('id', $id)->delete();
    }
}
