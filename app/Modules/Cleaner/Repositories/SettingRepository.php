<?php

namespace App\Modules\Cleaner\Repositories;

use App\Models\Traces\TraceClearingSetting;
use App\Modules\Cleaner\Contracts\Repositories\SettingRepositoryInterface;
use App\Modules\Cleaner\Entities\SettingObject;
use Illuminate\Database\Eloquent\Builder;

class SettingRepository implements SettingRepositoryInterface
{
    public function find(
        ?string $type = null,
        ?bool $typeIsNotNull = null,
        ?bool $onlyData = null,
        ?int $excludeId = null,
        ?bool $deleted = null,
        bool $orderByTypeAndOnlyData = false
    ): array {
        return TraceClearingSetting::query()
            ->when(
                !is_null($type),
                fn(Builder $query) => $query->where('type', $type)
            )
            ->when(
                !is_null($excludeId),
                fn(Builder $query) => $query->where('id', '!=', $excludeId)
            )
            ->when(
                !is_null($onlyData),
                fn(Builder $query) => $query->where('only_data', $onlyData)
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
            ->when(
                $orderByTypeAndOnlyData,
                fn(Builder $query) => $query->orderBy('type')->orderBy('only_data'),
                fn(Builder $query) => $query->orderByDesc('created_at')
            )
            ->get()
            ->map(
                fn(TraceClearingSetting $setting) => $this->modelToDto($setting),
            )
            ->toArray();
    }

    public function findOneById(int $id): ?SettingObject
    {
        /** @var TraceClearingSetting|null $setting */
        $setting = TraceClearingSetting::query()->find($id);

        if (!$setting) {
            return null;
        }

        return $this->modelToDto($setting);
    }

    public function create(int $daysLifetime, ?string $type, bool $onlyData): int
    {
        $newSetting = new TraceClearingSetting();

        $newSetting->days_lifetime = $daysLifetime;
        $newSetting->type          = $type;
        $newSetting->only_data     = $onlyData;

        $newSetting->saveOrFail();

        return $newSetting->id;
    }

    public function update(int $id, int $daysLifetime, bool $onlyData): bool
    {
        return (bool) TraceClearingSetting::query()
            ->where('id', $id)
            ->update([
                'days_lifetime' => $daysLifetime,
                'deleted_at'    => null,
                'only_data'     => $onlyData,
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

    private function modelToDto(TraceClearingSetting $setting): SettingObject
    {
        return new SettingObject(
            id: $setting->id,
            daysLifetime: $setting->days_lifetime,
            type: $setting->type,
            onlyData: $setting->only_data,
            deleted: !is_null($setting->deleted_at),
            createdAt: $setting->created_at,
            updatedAt: $setting->updated_at
        );
    }
}
