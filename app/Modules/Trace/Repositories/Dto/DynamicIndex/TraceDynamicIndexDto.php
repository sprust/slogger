<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\DynamicIndex;

use Illuminate\Support\Carbon;

readonly class TraceDynamicIndexDto
{
    /**
     * @param string[]                    $collectionNames
     * @param TraceDynamicIndexFieldDto[] $fields
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $indexName,
        public array $collectionNames,
        public array $fields,
        public bool $inProcess,
        public bool $created,
        public ?string $error,
        public Carbon $actualUntilAt,
        public Carbon $createdAt
    ) {
    }
}
