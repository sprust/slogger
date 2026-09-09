<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Repositories;

use App\Models\Watchers\Watcher;
use App\Modules\Watcher\Repositories\Dto\WatcherDto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

/**
 * Rows in and rows out. Nothing here interprets a watcher's type or its settings — that
 * is the domain's, and it is one place there rather than a second copy here.
 */
readonly class WatcherRepository
{
    /**
     * @return WatcherDto[]
     */
    public function find(?bool $enabled = null): array
    {
        return Watcher::query()
            ->when(
                !is_null($enabled),
                fn(Builder $query) => $query->where('enabled', $enabled)
            )
            ->orderBy('id')
            ->get()
            ->map(fn(Watcher $watcher) => $this->makeDto($watcher))
            ->all();
    }

    public function findById(int $id): ?WatcherDto
    {
        $watcher = Watcher::query()->find($id);

        return $watcher instanceof Watcher ? $this->makeDto($watcher) : null;
    }

    /**
     * @param array<string, mixed>      $settings
     * @param array<string, mixed>|null $traceMatch
     */
    public function create(
        string $name,
        string $type,
        bool $enabled,
        int $cooldownSeconds,
        ?int $notificationChannelId,
        array $settings,
        ?array $traceMatch,
        ?Carbon $collectSince
    ): WatcherDto {
        $watcher = new Watcher();

        $watcher->name             = $name;
        $watcher->type             = $type;
        $watcher->enabled          = $enabled;
        $watcher->cooldown_seconds = $cooldownSeconds;
        $watcher->settings         = $settings;
        $watcher->trace_match      = $traceMatch;
        $watcher->collect_since    = $collectSince;

        $watcher->notification_channel_id = $notificationChannelId;

        $watcher->saveOrFail();

        return $this->makeDto($watcher);
    }

    /**
     * @param array<string, mixed>      $settings
     * @param array<string, mixed>|null $traceMatch
     */
    public function update(
        int $id,
        string $name,
        bool $enabled,
        int $cooldownSeconds,
        ?int $notificationChannelId,
        array $settings,
        ?array $traceMatch,
        ?Carbon $collectSince
    ): void {
        Watcher::query()
            ->where('id', $id)
            ->update([
                'name'                    => $name,
                'enabled'                 => $enabled,
                'cooldown_seconds'        => $cooldownSeconds,
                'settings'                => $settings,
                'notification_channel_id' => $notificationChannelId,
                'trace_match'             => $traceMatch,
                'collect_since'           => $collectSince,
                'updated_at'              => Carbon::now(),
            ]);
    }

    /**
     * Both of these go through the base query, so that `updated_at` is left alone.
     *
     * Eloquent's own update() adds it, and these two run on every watcher every minute:
     * the column would then say when the watcher was last looked at rather than when
     * somebody last changed it, which is the one thing it is for. The soft-delete scope
     * still applies — toBase() applies the scopes before it hands the query over.
     */
    public function updateCheckedAt(int $id, Carbon $checkedAt): void
    {
        Watcher::query()
            ->where('id', $id)
            ->toBase()
            ->update(['last_checked_at' => $checkedAt]);
    }

    public function updateTriggeredAt(int $id, Carbon $triggeredAt): void
    {
        Watcher::query()
            ->where('id', $id)
            ->toBase()
            ->update(['last_triggered_at' => $triggeredAt]);
    }

    public function delete(int $id): void
    {
        Watcher::query()->where('id', $id)->delete();
    }

    private function makeDto(Watcher $watcher): WatcherDto
    {
        return new WatcherDto(
            id: $watcher->id,
            name: $watcher->name,
            type: $watcher->type,
            enabled: $watcher->enabled,
            cooldownSeconds: $watcher->cooldown_seconds,
            notificationChannelId: $watcher->notification_channel_id,
            settings: $watcher->settings,
            traceMatch: $watcher->trace_match,
            collectSince: $watcher->collect_since,
            lastCheckedAt: $watcher->last_checked_at,
            lastTriggeredAt: $watcher->last_triggered_at,
            createdAt: $watcher->created_at,
            updatedAt: $watcher->updated_at
        );
    }
}
