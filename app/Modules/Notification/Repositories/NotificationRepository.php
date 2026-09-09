<?php

declare(strict_types=1);

namespace App\Modules\Notification\Repositories;

use App\Models\Notifications\Notification;
use App\Modules\Notification\Entities\NotificationObject;
use App\Modules\Notification\Enums\NotificationKindEnum;
use Illuminate\Support\Carbon;
use SConcur\Bson\Exceptions\InvalidBsonValueException;
use SConcur\Bson\ObjectId;
use SConcur\Bson\UTCDateTime;

readonly class NotificationRepository
{
    public function findById(string $id): ?NotificationObject
    {
        $objectId = $this->objectId($id);

        if (is_null($objectId)) {
            return null;
        }

        $document = Notification::sconcur()->findOne(['_id' => $objectId]);

        return is_null($document) ? null : $this->makeObject($document);
    }

    /**
     * @return NotificationObject[]
     */
    public function findByChannelId(int $channelId, int $limit): array
    {
        $cursor = Notification::sconcur()->find(
            filter: ['channelId' => $channelId],
            sort: ['_id' => -1],
            limit: $limit,
        );

        $notifications = [];

        foreach ($cursor as $document) {
            $notifications[] = $this->makeObject($document);
        }

        return $notifications;
    }

    public function create(
        int $channelId,
        ?int $watcherId,
        ?string $incidentId,
        NotificationKindEnum $kind,
        string $text,
        Carbon $createdAt
    ): NotificationObject {
        $result = Notification::sconcur()->insertOne([
            'channelId'  => $channelId,
            'watcherId'  => $watcherId,
            'incidentId' => $incidentId,
            'kind'       => $kind->value,
            'text'       => $text,
            'sentAt'     => null,
            'error'      => null,
            'createdAt'  => new UTCDateTime($createdAt),
        ]);

        return new NotificationObject(
            id: (string) $result->insertedId,
            channelId: $channelId,
            watcherId: $watcherId,
            incidentId: $incidentId,
            kind: $kind,
            text: $text,
            sentAt: null,
            error: null,
            createdAt: $createdAt
        );
    }

    public function markSent(string $id, Carbon $sentAt): void
    {
        $objectId = $this->objectId($id);

        if (is_null($objectId)) {
            return;
        }

        Notification::sconcur()->updateOne(
            filter: ['_id' => $objectId],
            update: [
                '$set' => [
                    'sentAt' => new UTCDateTime($sentAt),
                    'error'  => null,
                ],
            ],
        );
    }

    public function markFailed(string $id, string $error): void
    {
        $objectId = $this->objectId($id);

        if (is_null($objectId)) {
            return;
        }

        Notification::sconcur()->updateOne(
            filter: ['_id' => $objectId],
            update: ['$set' => ['error' => $error]],
        );
    }

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
    private function makeObject(array $document): NotificationObject
    {
        return new NotificationObject(
            id: (string) $document['_id'],
            channelId: (int) $document['channelId'],
            watcherId: isset($document['watcherId']) ? (int) $document['watcherId'] : null,
            incidentId: isset($document['incidentId']) ? (string) $document['incidentId'] : null,
            kind: NotificationKindEnum::from((string) $document['kind']),
            text: (string) $document['text'],
            sentAt: $this->readDate($document, 'sentAt'),
            error: isset($document['error']) ? (string) $document['error'] : null,
            createdAt: $this->readDate($document, 'createdAt') ?? Carbon::now()
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
