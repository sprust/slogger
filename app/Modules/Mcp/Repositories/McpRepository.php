<?php

declare(strict_types=1);

namespace App\Modules\Mcp\Repositories;

use App\Models\Mcps\Mcp;
use App\Modules\Mcp\Entities\McpObject;
use Illuminate\Support\Carbon;

readonly class McpRepository
{
    public function create(string $name, string $token): McpObject
    {
        $mcp = new Mcp();

        $mcp->name    = $name;
        $mcp->token   = $token;
        $mcp->enabled = true;

        $mcp->saveOrFail();

        $mcp->refresh();

        return $this->makeObject($mcp);
    }

    /**
     * @return McpObject[]
     */
    public function find(): array
    {
        return Mcp::query()
            ->orderBy('id')
            ->get()
            ->map(fn(Mcp $mcp) => $this->makeObject($mcp))
            ->all();
    }

    public function findOneById(int $id): ?McpObject
    {
        $mcp = Mcp::query()->find($id);

        return $mcp instanceof Mcp ? $this->makeObject($mcp) : null;
    }

    public function findOneByToken(string $token): ?McpObject
    {
        $mcp = Mcp::query()->where('token', $token)->first();

        return $mcp instanceof Mcp ? $this->makeObject($mcp) : null;
    }

    public function update(int $id, string $name, bool $enabled): void
    {
        Mcp::query()
            ->where('id', $id)
            ->update([
                'name'    => $name,
                'enabled' => $enabled,
            ]);
    }

    public function updateToken(int $id, string $token): void
    {
        Mcp::query()
            ->where('id', $id)
            ->update([
                'token' => $token,
            ]);
    }

    public function updateLastUsedAt(int $id, Carbon $lastUsedAt): void
    {
        Mcp::query()
            ->where('id', $id)
            ->update([
                'last_used_at' => $lastUsedAt,
            ]);
    }

    public function delete(int $id): bool
    {
        return Mcp::query()->where('id', $id)->delete() > 0;
    }

    private function makeObject(Mcp $mcp): McpObject
    {
        return new McpObject(
            id: $mcp->id,
            name: $mcp->name,
            token: $mcp->token,
            enabled: $mcp->enabled,
            lastUsedAt: $mcp->last_used_at,
            createdAt: $mcp->created_at,
            updatedAt: $mcp->updated_at
        );
    }
}
