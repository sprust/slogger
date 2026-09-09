<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Repositories;

use App\Models\Watchers\WatcherIncident;
use App\Modules\Watcher\Entities\WatcherIncidentObject;
use App\Modules\Watcher\Enums\WatcherIncidentStatusEnum;
use App\Modules\Watcher\Parameters\FindIncidentsParameters;
use Illuminate\Support\Carbon;
use SConcur\Bson\Exceptions\InvalidBsonValueException;
use SConcur\Bson\ObjectId;
use SConcur\Bson\UTCDateTime;

readonly class WatcherIncidentRepository
{
    /**
     * @return WatcherIncidentObject[]
     */
    public function find(FindIncidentsParameters $parameters): array
    {
        $filter = [];

        if (!is_null($parameters->status)) {
            $filter['status'] = $parameters->status->value;
        }

        if (!is_null($parameters->watcherId)) {
            $filter['watcherId'] = $parameters->watcherId;
        }

        $cursor = WatcherIncident::sconcur()->find(
            filter: $filter,
            // Still open first, then newest: the list is a work queue, and a closed
            // incident is history whatever its date. `opened` sorts after `closed`, which
            // is what the descending order is for; the id carries the time it was made.
            sort: ['status' => -1, '_id' => -1],
            limit: $parameters->perPage,
            skip: ($parameters->page - 1) * $parameters->perPage,
        );

        $incidents = [];

        foreach ($cursor as $document) {
            $incidents[] = $this->makeObject($document);
        }

        return $incidents;
    }

    public function findLastOpenByWatcherId(int $watcherId): ?WatcherIncidentObject
    {
        $cursor = WatcherIncident::sconcur()->find(
            filter: [
                'watcherId' => $watcherId,
                'status'    => WatcherIncidentStatusEnum::Opened->value,
            ],
            sort: ['_id' => -1],
            limit: 1,
        );

        foreach ($cursor as $document) {
            return $this->makeObject($document);
        }

        return null;
    }

    public function findById(string $id): ?WatcherIncidentObject
    {
        $objectId = $this->objectId($id);

        if (is_null($objectId)) {
            return null;
        }

        $document = WatcherIncident::sconcur()->findOne(['_id' => $objectId]);

        return is_null($document) ? null : $this->makeObject($document);
    }

    public function countOpen(): int
    {
        return WatcherIncident::sconcur()->countDocuments([
            'status' => WatcherIncidentStatusEnum::Opened->value,
        ]);
    }

    public function create(int $watcherId, Carbon $occurredAt): WatcherIncidentObject
    {
        $result = WatcherIncident::sconcur()->insertOne([
            'watcherId'      => $watcherId,
            'status'         => WatcherIncidentStatusEnum::Opened->value,
            'firstEventAt'   => new UTCDateTime($occurredAt),
            'lastEventAt'    => new UTCDateTime($occurredAt),
            'eventsCount'    => 0,
            'closedAt'       => null,
            'closedByUserId' => null,
        ]);

        return new WatcherIncidentObject(
            id: (string) $result->insertedId,
            watcherId: $watcherId,
            status: WatcherIncidentStatusEnum::Opened,
            firstEventAt: $occurredAt,
            lastEventAt: $occurredAt,
            eventsCount: 0,
            closedAt: null,
            closedByUserId: null
        );
    }

    public function incrementEventsCount(string $id, Carbon $lastEventAt): void
    {
        $objectId = $this->objectId($id);

        if (is_null($objectId)) {
            return;
        }

        WatcherIncident::sconcur()->updateOne(
            filter: ['_id' => $objectId],
            update: [
                // $inc rather than a read-modify-write: the counter is a denormalisation
                // of the events collection, and the database is the only thing that can
                // add to it without a window in which somebody else's event is lost.
                '$inc' => ['eventsCount' => 1],
                '$set' => ['lastEventAt' => new UTCDateTime($lastEventAt)],
            ],
        );
    }

    public function updateStatus(
        string $id,
        WatcherIncidentStatusEnum $status,
        ?Carbon $closedAt,
        ?int $closedByUserId
    ): void {
        $objectId = $this->objectId($id);

        if (is_null($objectId)) {
            return;
        }

        WatcherIncident::sconcur()->updateOne(
            filter: ['_id' => $objectId],
            update: [
                '$set' => [
                    'status'         => $status->value,
                    'closedAt'       => is_null($closedAt) ? null : new UTCDateTime($closedAt),
                    'closedByUserId' => $closedByUserId,
                ],
            ],
        );
    }

    /**
     * An id that is not one, answered with null rather than an exception.
     *
     * These arrive from the url, and a mistyped one is a 404 rather than a 500. The format
     * is not restated here — the value object is what knows it.
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
    private function makeObject(array $document): WatcherIncidentObject
    {
        return new WatcherIncidentObject(
            id: (string) $document['_id'],
            watcherId: (int) $document['watcherId'],
            status: WatcherIncidentStatusEnum::from((string) $document['status']),
            firstEventAt: $this->readDate($document, 'firstEventAt') ?? Carbon::now(),
            lastEventAt: $this->readDate($document, 'lastEventAt') ?? Carbon::now(),
            eventsCount: (int) ($document['eventsCount'] ?? 0),
            closedAt: $this->readDate($document, 'closedAt'),
            closedByUserId: isset($document['closedByUserId'])
                ? (int) $document['closedByUserId']
                : null
        );
    }

    /**
     * @param array<int|string, mixed> $document
     */
    private function readDate(array $document, string $key): ?Carbon
    {
        $value = $document[$key] ?? null;

        return $value instanceof UTCDateTime ? Carbon::parse($value->toDateTime()) : null;
    }
}
