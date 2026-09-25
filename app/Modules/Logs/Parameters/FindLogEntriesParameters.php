<?php

declare(strict_types=1);

namespace App\Modules\Logs\Parameters;

use App\Modules\Logs\Enums\LogCursorDirectionEnum;

readonly class FindLogEntriesParameters
{
    /**
     * @param list<string>      $fileIds
     * @param list<string>|null $levelKeys
     */
    public function __construct(
        public array $fileIds,
        public ?array $levelKeys,
        public ?int $fromTime,
        public ?int $toTime,
        public ?string $searchQuery,
        public ?string $cursor,
        public LogCursorDirectionEnum $direction,
        public int $perPage
    ) {
    }
}
