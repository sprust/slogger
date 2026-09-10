<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Domain\Services\Events;

use App\Modules\Watcher\Entities\Events\WatcherEventPayloadInterface;

/**
 * Both directions of one watcher type's event payload, in one file.
 *
 * The stored keys appear here and nowhere else: the checker returns an object, the
 * document is written from one and read back into one, so a key renamed on the way out
 * cannot be missed on the way in.
 */
interface WatcherEventPayloadMapperInterface
{
    /**
     * Null for a payload this build cannot read as this type.
     *
     * A stored event outlives the code that wrote it, so a document from before a number
     * existed is the ordinary case after a release. It keeps its place in the page and
     * shows no numbers, rather than being dropped — a page that comes back short is read
     * as the end of the list.
     *
     * @param array<string, mixed> $payload
     */
    public function read(array $payload): ?WatcherEventPayloadInterface;

    /**
     * @return array<string, mixed>
     */
    public function toDocument(WatcherEventPayloadInterface $payload): array;
}
