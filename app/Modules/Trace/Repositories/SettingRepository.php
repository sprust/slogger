<?php

namespace App\Modules\Trace\Repositories;

use App\Models\Traces\TraceClearingSetting;
use App\Modules\Trace\Repositories\Dto\SettingDto;
use App\Modules\Trace\Repositories\Interfaces\SettingRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;

class SettingRepository implements SettingRepositoryInterface
{
    public function find(?string $type = null, ?bool $typeIsNotNull = null, ?bool $deleted = null): array
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
            ->when(
                !is_null($deleted),
                fn(Builder $query) => $deleted
                    ? $query->whereNotNull('deleted_at')
                    : $query->whereNull('deleted_at')
            )
            ->orderByDesc('created_at')
            ->get()
            ->map(
                fn(TraceClearingSetting $setting) => $this->modelToDto($setting),
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

        return $this->modelToDto($setting);
    }

    public function create(int $daysLifetime, ?string $type): int
    {
        $newSetting = new TraceClearingSetting();

        $newSetting->days_lifetime = $daysLifetime;
        $newSetting->type          = $type;

        $newSetting->saveOrFail();

        return $newSetting->id;
    }

    public function update(int $id, int $daysLifetime): bool
    {
        return (bool) TraceClearingSetting::query()
            ->where('id', $id)
            ->update([
                'days_lifetime' => $daysLifetime,
                'deleted_at'    => null,
            ]);
    }

    public function delete(int $id): bool
    {
        return (bool) TraceClearingSetting::query()
            ->where('id', $id)
            ->update([
                'deleted_at' => now(),
            ]);
    }

    private function modelToDto(TraceClearingSetting $setting): SettingDto
    {
        return new SettingDto(
            id: $setting->id,
            daysLifetime: $setting->days_lifetime,
            type: $setting->type,
            deleted: !is_null($setting->deleted_at),
            createdAt: $setting->created_at,
            updatedAt: $setting->updated_at
        );
    }
}
