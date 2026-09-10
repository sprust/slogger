<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services;

use App\Modules\Watcher\Domain\Services\Types\WatcherTypeRegistry;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;
use App\Modules\Watcher\Enums\WatcherTypeEnum;
use App\Modules\Watcher\Repositories\Dto\WatcherIncidentEventDto;
use Psr\Log\LoggerInterface;

/**
 * Turns a stored event into an object of the type that wrote it.
 *
 * Shaped after WatcherFactory and for the same reason: the document carries json, what it
 * means is the type definition's, and this is where the two are put together.
 */
readonly class WatcherIncidentEventFactory
{
    public function __construct(
        private WatcherTypeRegistry $types,
        private LoggerInterface $logger
    ) {
    }

    /**
     * An unreadable payload leaves the event standing with nothing in it.
     *
     * Not dropped: a page that comes back short is read as the end of the list, and an
     * event whose numbers cannot be shown is still a time the watcher spoke. This is the
     * case of a document written before a number existed, which the TTL clears within a
     * month.
     */
    public function make(WatcherIncidentEventDto $dto, WatcherTypeEnum $type): WatcherIncidentEventObject
    {
        $payload = $this->types->for($type)->eventPayloadMapper()->read($dto->payload);

        if (is_null($payload)) {
            $this->logger->warning(
                sprintf('Watcher incident event [%s] could not be read as [%s]', $dto->id, $type->value)
            );
        }

        return new WatcherIncidentEventObject(
            id: $dto->id,
            incidentId: $dto->incidentId,
            payload: $payload,
            occurredAt: $dto->occurredAt
        );
    }
}
