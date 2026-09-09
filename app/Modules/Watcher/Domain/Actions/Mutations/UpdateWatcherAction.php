<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Mutations;

use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherAction;
use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Domain\Services\WatcherCollectionStart;
use App\Modules\Watcher\Domain\Services\WatcherMatchFactory;
use App\Modules\Watcher\Entities\Settings\HasTraceFilterInterface;
use App\Modules\Watcher\Entities\Settings\WatcherSettingsInterface;
use App\Modules\Watcher\Entities\WatcherMatchObject;
use App\Modules\Watcher\Parameters\UpdateWatcherParameters;
use App\Modules\Watcher\Repositories\WatcherRepository;
use App\Modules\Watcher\Repositories\WatcherTimelineRepository;
use Illuminate\Support\Carbon;

readonly class UpdateWatcherAction
{
    public function __construct(
        private WatcherRepository $watcherRepository,
        private WatcherTimelineRepository $timelineRepository,
        private WatcherTypeRegistry $types,
        private WatcherMatchFactory $matchFactory,
        private FindWatcherAction $findWatcherAction,
        private WatcherCollectionStart $collectionStart
    ) {
    }

    /**
     * @throws WatcherNotFoundException
     */
    public function handle(UpdateWatcherParameters $parameters): void
    {
        $watcher = $this->findWatcherAction->handle($parameters->id);

        if (is_null($watcher)) {
            throw new WatcherNotFoundException($parameters->id);
        }

        $settings = $this->types->for($watcher->type)->makeSettings($parameters->settings);

        $match = $this->matchFactory->make($settings);

        $filterChanged = !$this->sameMatch($watcher->match, $match);

        $deepened = $this->depthOf($settings) > $this->depthOf($watcher->settings);

        if ($filterChanged) {
            $this->timelineRepository->delete($watcher->id);
        }

        $this->watcherRepository->update(
            id: $parameters->id,
            name: $parameters->name,
            enabled: $parameters->enabled,
            cooldownSeconds: $parameters->cooldownSeconds,
            settings: $settings->toArray(),
            traceMatch: $this->matchFactory->toArray($match),
            collectSince: $filterChanged || $deepened || (!$watcher->enabled && $parameters->enabled)
                ? $this->collectionStart->afterNextReload(Carbon::now())
                : $watcher->collectSince
        );
    }

    /**
     * How far back these settings ask a checker to look. Nothing, for a watcher that reads
     * counters rather than a line.
     */
    private function depthOf(WatcherSettingsInterface $settings): int
    {
        return $settings instanceof HasTraceFilterInterface ? $settings->timelineDepthMinutes() : 0;
    }

    /**
     * Compared by value: what matters is whether the receiver will now count something
     * else, not whether the column was rewritten.
     */
    private function sameMatch(?WatcherMatchObject $first, ?WatcherMatchObject $second): bool
    {
        if (is_null($first) || is_null($second)) {
            return is_null($first) && is_null($second);
        }

        return $this->sorted($first->serviceIds) === $this->sorted($second->serviceIds)
            && $this->sorted($first->types) === $this->sorted($second->types)
            && $this->sorted($first->tags) === $this->sorted($second->tags);
    }

    /**
     * @template T of int|string
     *
     * @param T[] $values
     *
     * @return T[]
     */
    private function sorted(array $values): array
    {
        sort($values);

        return $values;
    }
}
