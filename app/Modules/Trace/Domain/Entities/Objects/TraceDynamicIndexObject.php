<?php

namespace App\Modules\Trace\Domain\Entities\Objects;

use Illuminate\Support\Carbon;

readonly class TraceDynamicIndexObject
{
    /**
     * @param TraceDynamicIndexFieldObject[] $fields
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $fields,
        public bool $inProcess,
        public bool $created,
        public ?string $error,
        public Carbon $actualUntilAt,
        public Carbon $createdAt
    ) {
    }
}
