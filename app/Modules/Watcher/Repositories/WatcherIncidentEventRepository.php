<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Repositories;

use App\Models\Watchers\WatcherIncidentEvent;
use App\Modules\Watcher\Repositories\Dto\WatcherIncidentEventDto;
use Illuminate\Support\Carbon;
use SConcur\Bson\Exceptions\InvalidBsonValueException;
use SConcur\Bson\ObjectId;
use SConcur\Bson\UTCDateTime;

/**
 * Documents in and documents out. Nothing here interprets a payload — that takes knowing
 * which watcher type wrote it, and the type belongs to the watcher, not to the event.
 */
readonly class WatcherIncidentEventRepository
{
    /**
     * @return WatcherIncidentEventDto[]
     */
    public function findByIncidentId(string $incidentId, int $page, int $perPage): array
    {
        $objectId = $this->objectId($incidentId);

        if (is_null($objectId)) {
            return [];
        }

        $cursor = WatcherIncidentEvent::sconcur()->find(
            filter: ['incidentId' => $objectId],
            // Newest first, and the id carries the moment it was written — an event is
            // never edited, so nothing else can order them.
            sort: ['_id' => -1],
            limit: $perPage,
            skip: ($page - 1) * $perPage,
        );

        $events = [];

        foreach ($cursor as $document) {
            $events[] = $this->makeDto($document);
        }

        return $events;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(string $incidentId, Carbon $occurredAt, array $payload): void
    {
        $objectId = $this->objectId($incidentId);

        if (is_null($objectId)) {
            return;
        }

        WatcherIncidentEvent::sconcur()->insertOne([
            'incidentId' => $objectId,
            'occurredAt' => new UTCDateTime($occurredAt),
            'payload'    => $payload,
        ]);
    }

    /**
     * An id that is not one, answered with nothing rather than an exception: these arrive
     * from the url. The format is not restated here — the value object is what knows it.
     */
    private function objectId(string $id): ?ObjectId
    {
        try {
            return new ObjectId($id);
        } catch (InvalidBsonValueException) {
            return null;
        }
    }

    /**
     * @param array<int|string, mixed> $document
     */
    private function makeDto(array $document): WatcherIncidentEventDto
    {
        $occurredAt = $document['occurredAt'] ?? null;

        $payload = $document['payload'] ?? null;

        /** @var array<string, mixed> $payload */
        $payload = is_array($payload) ? $payload : [];

        return new WatcherIncidentEventDto(
            id: (string) ($document['_id'] ?? ''),
            incidentId: (string) ($document['incidentId'] ?? ''),
            payload: $payload,
            occurredAt: $occurredAt instanceof UTCDateTime
                ? Carbon::parse($occurredAt->toDateTime())
                : Carbon::now()
        );
    }
}
