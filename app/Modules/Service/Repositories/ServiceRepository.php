<?php

declare(strict_types=1);

namespace App\Modules\Service\Repositories;

use App\Models\Services\Service;
use App\Modules\Service\Entities\ServiceObject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ServiceRepository
{
    /**
     * @param int[]|null $ids
     *
     * @return ServiceObject[]
     */
    public function find(?array $ids = null): array
    {
        return Service::query()
            ->when(
                !is_null($ids),
                fn(Builder $query) => $query->whereIn('id', $ids ?? [])
            )
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(
                fn(Service $service) => $this->makeObjectByModel($service)
            )
            ->toArray();
    }

    public function findOneById(int $id): ?ServiceObject
    {
        $service = Service::query()->find($id);

        return $service instanceof Service ? $this->makeObjectByModel($service) : null;
    }

    /**
     * @return int[]
     */
    public function findIds(): array
    {
        return Service::query()
            ->pluck('id')
            ->map(static fn(mixed $id): int => (int) $id)
            ->all();
    }

    public function create(string $name, string $uniqueKey): ServiceObject
    {
        $newService = new Service();

        $newService->name       = $name;
        $newService->unique_key = $uniqueKey;
        $newService->api_token  = Str::random(50);
        $newService->position   = ((int) Service::query()->max('position')) + 1;

        $newService->saveOrFail();

        $newService->refresh();

        return $this->makeObjectByModel($newService);
    }

    public function update(int $id, string $name, string $uniqueKey): void
    {
        Service::query()
            ->where('id', $id)
            ->update([
                'name'       => $name,
                'unique_key' => $uniqueKey,
            ]);
    }

    /**
     * @param int[] $ids
     */
    public function updatePositions(array $ids): void
    {
        $ids = array_values($ids);

        if (count($ids) === 0) {
            return;
        }

        $cases    = [];
        $bindings = [];

        foreach ($ids as $index => $id) {
            $cases[]    = 'WHEN ? THEN ?';
            $bindings[] = $id;
            $bindings[] = $index + 1;
        }

        DB::update(
            sprintf(
                'UPDATE %s SET position = CASE id %s END WHERE id IN (%s)',
                new Service()->getTable(),
                implode(' ', $cases),
                implode(', ', array_fill(0, count($ids), '?'))
            ),
            [...$bindings, ...$ids]
        );
    }

    public function isExistByUniqueKey(string $uniqueKey, ?int $exceptId = null): bool
    {
        return Service::query()
            ->where('unique_key', $uniqueKey)
            ->when(
                !is_null($exceptId),
                fn(Builder $query) => $query->where('id', '!=', $exceptId)
            )
            ->exists();
    }

    private function makeObjectByModel(Service $service): ServiceObject
    {
        return new ServiceObject(
            id: $service->id,
            name: $service->name,
            uniqueKey: $service->unique_key,
            apiToken: $service->api_token,
            position: $service->position,
            createdAt: $service->created_at
        );
    }
}
