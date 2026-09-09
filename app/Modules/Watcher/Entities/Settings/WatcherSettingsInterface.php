<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities\Settings;

/**
 * The tuned numbers of one watcher. Every implementation belongs to exactly one
 * WatcherTypeEnum case; the mapper is what knows which.
 */
interface WatcherSettingsInterface
{
    /**
     * The settings as they are stored and as the panel edits them.
     *
     * Here rather than in the mapper because the object is the one place that knows which
     * fields it has: the mapper reads them back, and a resource renders them, and neither
     * should carry a second copy of the list.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
