<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Mutations;

use App\Modules\Watcher\Domain\Actions\Queries\FindWatcherAction;
use App\Modules\Watcher\Domain\Exceptions\WatcherNotFoundException;
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Domain\Services\WatcherMatchFactory;
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
        private FindWatcherAction $findWatcherAction
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

        // The type comes from the row, not from the request: it decides the shape of the
        // settings, and letting an edit change it would leave the stored numbers meaning
        // something else.
        $settings = $this->types->for($watcher->type)->makeSettings($parameters->settings);

        $match = $this->matchFactory->make($settings);

        $filterChanged = !$this->sameMatch($watcher->match, $match);

        // A changed filter makes the line already collected answer a different question,
        // so it is thrown away and the watcher starts collecting again. Anything else —
        // a rename, a new threshold, a longer window — reads the same traces, and keeping
        // the history is what lets the new setting take effect immediately.
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
            collectSince: $filterChanged || !$watcher->enabled && $parameters->enabled
                ? Carbon::now()
                : $watcher->collectSince
        );
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
