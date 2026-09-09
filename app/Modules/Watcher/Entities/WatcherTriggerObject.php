<?php

declare(strict_types=1);

namespace App\Modules\Watcher\Entities;

readonly class WatcherTriggerObject
{
    /**
     * @param array<string, scalar>            $settings the numbers the watcher was set to,
     *                                                   keyed as its settings object keys them
     * @param array<string, scalar>            $measured what the checker saw
     * @param array<int, array<string, mixed>> $groups
     */
    public function __construct(
        public array $settings,
        public array $measured,
        public array $groups = []
    ) {
    }
}
