<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Repositories;

use App\Models\Watchers\WatcherIncidentEvent;
use App\Modules\Watcher\Entities\WatcherIncidentEventObject;
use App\Modules\Watcher\Entities\WatcherTriggerObject;
use Illuminate\Support\Carbon;
use SConcur\Bson\Exceptions\InvalidBsonValueException;
use SConcur\Bson\ObjectId;
use SConcur\Bson\UTCDateTime;

readonly class WatcherIncidentEventRepository
{
    /**
     * @return WatcherIncidentEventObject[]
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
            $events[] = $this->makeObject($document);
        }

        return $events;
    }

    public function create(string $incidentId, Carbon $occurredAt, WatcherTriggerObject $trigger): void
    {
        $objectId = $this->objectId($incidentId);

        if (is_null($objectId)) {
            return;
        }

        WatcherIncidentEvent::sconcur()->insertOne([
            'incidentId' => $objectId,
            'occurredAt' => new UTCDateTime($occurredAt),
            'payload'    => [
                'settings' => $trigger->settings,
                'measured' => $trigger->measured,
                'groups'   => $trigger->groups,
            ],
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
    private function makeObject(array $document): WatcherIncidentEventObject
    {
        $occurredAt = $document['occurredAt'] ?? null;

        $payload = $document['payload'] ?? [];

        /** @var array<string, mixed> $payload */
        $payload = is_array($payload) ? $payload : [];

        // A payload written before the split has its numbers at the top level, and which
        // of them were settings is not recoverable from the document. They are read as
        // measured until the TTL retires the last of them.
        $split = array_key_exists('settings', $payload) || array_key_exists('measured', $payload);

        return new WatcherIncidentEventObject(
            id: (string) $document['_id'],
            incidentId: (string) $document['incidentId'],
            settings: $split ? $this->scalars($payload['settings'] ?? null) : [],
            measured: $split
                ? $this->scalars($payload['measured'] ?? null)
                : $this->scalars($payload),
            groups: $this->groups($payload['groups'] ?? null),
            occurredAt: $occurredAt instanceof UTCDateTime
                ? Carbon::parse($occurredAt->toDateTime())
                : Carbon::now()
        );
    }

    /**
     * @return array<string, scalar>
     */
    private function scalars(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $scalars = [];

        foreach ($values as $key => $value) {
            if (is_string($key) && is_scalar($value)) {
                $scalars[$key] = $value;
            }
        }

        return $scalars;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function groups(mixed $groups): array
    {
        if (!is_array($groups)) {
            return [];
        }

        $result = [];

        foreach ($groups as $group) {
            if (is_array($group)) {
                /** @var array<string, mixed> $group */
                $result[] = $group;
            }
        }

        return $result;
    }
}
