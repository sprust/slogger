<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services;

use App\Modules\Common\Helpers\ArrayValueGetter;
use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Entities\WatcherMatchObject;
use App\Modules\Watcher\Entities\WatcherObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Repositories\Dto\WatcherDto;
use Psr\Log\LoggerInterface;

/**
 * Turns a stored row into a watcher.
 *
 * The row carries a type as a string and settings as json; what those mean is the
 * definition's, and this is where the two are put together. The repository hands over what
 * it read and nothing more.
 */
readonly class WatcherFactory
{
    public function __construct(
        private WatcherTypeRegistry $types,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Null for a row of a type this build does not know.
     *
     * A stored type is data, and data outlives the code that wrote it: a watcher created
     * by a newer build, or one whose type a rollback took away, is a row like any other.
     * Throwing on it would take out the whole pass — the check task reads every watcher
     * before it checks any of them — so an unreadable row is skipped, the way the receiver
     * skips a filter whose version it does not recognise.
     */
    public function make(WatcherDto $dto): ?WatcherObject
    {
        $type = WatcherTypeEnum::tryFrom($dto->type);

        if (is_null($type)) {
            $this->logger->warning("Watcher [$dto->id] has an unknown type [$dto->type] and was skipped");

            return null;
        }

        return new WatcherObject(
            id: $dto->id,
            name: $dto->name,
            type: $type,
            enabled: $dto->enabled,
            cooldownSeconds: $dto->cooldownSeconds,
            settings: $this->types->for($type)->makeSettings($dto->settings),
            match: $this->makeMatch($dto->traceMatch),
            collectSince: $dto->collectSince,
            lastCheckedAt: $dto->lastCheckedAt,
            lastTriggeredAt: $dto->lastTriggeredAt,
            createdAt: $dto->createdAt,
            updatedAt: $dto->updatedAt
        );
    }

    /**
     * @param array<string, mixed>|null $raw
     */
    private function makeMatch(?array $raw): ?WatcherMatchObject
    {
        if (is_null($raw)) {
            return null;
        }

        return new WatcherMatchObject(
            serviceIds: array_values(ArrayValueGetter::arrayIntNull($raw, 'service_ids') ?? []),
            types: array_values(ArrayValueGetter::arrayStringNull($raw, 'types') ?? []),
            tags: array_values(ArrayValueGetter::arrayStringNull($raw, 'tags') ?? []),
            version: ArrayValueGetter::intNull($raw, 'v') ?? WatcherMatchObject::VERSION
        );
    }
}
