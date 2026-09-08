<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Actions\Queries;

use App\Modules\Watcher\Repositories\Dto\WatcherDto;
use App\Modules\Watcher\Repositories\WatcherRepository;

/**
 * Every watcher there is, by id alone.
 *
 * Read from the rows rather than from the watchers the factory managed to make: a row of a
 * type this build cannot read is skipped by the checks, and it still owns its line — the
 * receiver reads the table directly and knows nothing about types, so it goes on writing
 * one. The orphan sweep asks this instead, or it would delete that line every minute.
 */
readonly class FindWatcherIdsAction
{
    public function __construct(
        private WatcherRepository $watcherRepository
    ) {
    }

    /**
     * @return int[]
     */
    public function handle(): array
    {
        return array_map(
            static fn(WatcherDto $dto): int => $dto->id,
            $this->watcherRepository->find()
        );
    }
}
