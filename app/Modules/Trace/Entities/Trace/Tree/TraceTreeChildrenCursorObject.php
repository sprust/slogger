<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

use InvalidArgumentException;

/**
 * Where a page of a node's children stopped: the loggedAt of its last child in
 * milliseconds, and that child's _id to tell apart two that share a millisecond.
 *
 * Travels to the panel and back as `<loggedAtMs>:<_id>`.
 */
readonly class TraceTreeChildrenCursorObject
{
    public const string PATTERN = '/^\d{1,16}:[0-9a-f]{24}$/';

    public function __construct(
        public int $loggedAtMs,
        public string $id,
    ) {
    }

    public static function fromString(string $cursor): self
    {
        if (preg_match(self::PATTERN, $cursor) !== 1) {
            throw new InvalidArgumentException("Invalid tree children cursor: $cursor");
        }

        [$loggedAtMs, $id] = explode(':', $cursor, 2);

        return new self(
            loggedAtMs: (int) $loggedAtMs,
            id: $id,
        );
    }

    public function toString(): string
    {
        return "$this->loggedAtMs:$this->id";
    }
}
