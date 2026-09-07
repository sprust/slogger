<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Repositories;

use App\Models\Watchers\Watcher;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\WatcherMatchObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Repositories\Services\WatcherMatchFactory;
use App\Modules\Watcher\Repositories\Services\WatcherSettingsMapper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

readonly class WatcherRepository
{
    public function __construct(
        private WatcherSettingsMapper $settingsMapper,
        private WatcherMatchFactory $matchFactory
    ) {
    }

    /**
     * @return WatcherObject[]
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
            ->map(fn(Watcher $watcher) => $this->makeObject($watcher))
            ->all();
    }

    public function findById(int $id): ?WatcherObject
    {
        $watcher = Watcher::query()->find($id);

        return $watcher instanceof Watcher ? $this->makeObject($watcher) : null;
    }

    public function create(
        string $name,
        WatcherTypeEnum $type,
        bool $enabled,
        int $cooldownSeconds,
        WatcherSettingsInterface $settings,
        ?WatcherMatchObject $match,
        ?Carbon $collectSince
    ): WatcherObject {
        $watcher = new Watcher();

        $watcher->name             = $name;
        $watcher->type             = $type->value;
        $watcher->enabled          = $enabled;
        $watcher->cooldown_seconds = $cooldownSeconds;
        $watcher->settings         = $this->settingsMapper->toArray($settings);
        $watcher->trace_match      = $this->matchFactory->toArray($match);
        $watcher->collect_since    = $collectSince;

        $watcher->saveOrFail();

        return $this->makeObject($watcher);
    }

    /**
     * `collectSince` is passed rather than kept because it is not a property of the edit
     * but a decision about it: a filter that changed invalidates the line already
     * collected, a rename does not.
     */
    public function update(
        int $id,
        string $name,
        bool $enabled,
        int $cooldownSeconds,
        WatcherSettingsInterface $settings,
        ?WatcherMatchObject $match,
        ?Carbon $collectSince
    ): void {
        Watcher::query()
            ->where('id', $id)
            ->update([
                'name'             => $name,
                'enabled'          => $enabled,
                'cooldown_seconds' => $cooldownSeconds,
                'settings'         => $this->settingsMapper->toArray($settings),
                'trace_match'      => $this->matchFactory->toArray($match),
                'collect_since'    => $collectSince,
                'updated_at'       => Carbon::now(),
            ]);
    }

    public function updateCheckedAt(int $id, Carbon $checkedAt): void
    {
        Watcher::query()
            ->where('id', $id)
            ->update(['last_checked_at' => $checkedAt]);
    }

    public function updateTriggeredAt(int $id, Carbon $triggeredAt): void
    {
        Watcher::query()
            ->where('id', $id)
            ->update(['last_triggered_at' => $triggeredAt]);
    }

    public function delete(int $id): void
    {
        Watcher::query()->where('id', $id)->delete();
    }

    private function makeObject(Watcher $watcher): WatcherObject
    {
        $type = WatcherTypeEnum::from($watcher->type);

        return new WatcherObject(
            id: $watcher->id,
            name: $watcher->name,
            type: $type,
            enabled: $watcher->enabled,
            cooldownSeconds: $watcher->cooldown_seconds,
            settings: $this->settingsMapper->toObject($type, $watcher->settings),
            match: $this->matchFactory->fromArray($watcher->trace_match),
            collectSince: $watcher->collect_since,
            lastCheckedAt: $watcher->last_checked_at,
            lastTriggeredAt: $watcher->last_triggered_at,
            createdAt: $watcher->created_at,
            updatedAt: $watcher->updated_at
        );
    }
}
