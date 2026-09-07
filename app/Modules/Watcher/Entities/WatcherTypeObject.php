<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

use App\Modules\Watcher\Enums\WatcherTypeEnum;

/**
 * What a watcher of one type can be configured with.
 *
 * The panel builds its form from this rather than from a list of its own, so that adding
 * a field means changing the settings object and nothing else.
 */
readonly class WatcherTypeObject
{
    /**
     * @param WatcherTypeFieldObject[] $fields
     */
    public function __construct(
        public WatcherTypeEnum $type,
        public string $title,
        public string $description,
        public int $defaultCooldownSeconds,
        public bool $hasTraceFilter,
        public array $fields
    ) {
    }
}
