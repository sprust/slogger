<?php

namespace App\Modules\Trace\Repositories\Dto\DynamicIndex;

use Illuminate\Support\Carbon;

readonly class TraceDynamicIndexDto
{
    /**
     * @param TraceDynamicIndexFieldDto[] $fields
     */
    public function __construct(
        public string $id,
        public string $indexName,
        public string $fieldsKey,
        public ?Carbon $loggedAtFrom,
        public ?Carbon $loggedAtTo,
        public array $fields,
        public bool $inProcess,
        public bool $created,
        public ?string $error,
        public Carbon $actualUntilAt,
        public Carbon $createdAt
    ) {
    }
}
