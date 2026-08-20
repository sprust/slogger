<?php

declare(strict_types=1);

namespace App\Modules\Service\Repositories;

use App\Models\Services\Service;
use App\Modules\Service\Entities\ServiceObject;
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
        $sql      = 'SELECT id, name, api_token FROM services';
        $bindings = [];

        if (!is_null($ids)) {
            if (count($ids) === 0) {
                return [];
            }

            $placeholders = implode(', ', array_fill(0, count($ids), '?'));

            $sql .= " WHERE id IN ($placeholders)";

            $bindings = array_values($ids);
        }

        $rows = Service::sconcur()->fetchAll($sql, $bindings);

        return array_map(
            fn(array $row) => $this->makeObjectByRow($row),
            $rows
        );
    }

    public function create(string $name, string $uniqueKey): ServiceObject
    {
        $apiToken = Str::random(50);

        $now = now()->toDateTimeString();

        $result = Service::sconcur()->exec(
            'INSERT INTO services (name, unique_key, api_token, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
            [$name, $uniqueKey, $apiToken, $now, $now]
        );

        return new ServiceObject(
            id: $result->lastInsertId,
            name: $name,
            apiToken: $apiToken
        );
    }

    public function isExistByUniqueKey(string $uniqueKey): bool
    {
        $rows = Service::sconcur()->fetchAll(
            'SELECT 1 FROM services WHERE unique_key = ? LIMIT 1',
            [$uniqueKey]
        );

        return count($rows) > 0;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function makeObjectByRow(array $row): ServiceObject
    {
        return new ServiceObject(
            id: (int) $row['id'],
            name: (string) $row['name'],
            apiToken: (string) $row['api_token']
        );
    }
}
