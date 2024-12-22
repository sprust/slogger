<?php

declare(strict_types=1);

namespace App\Modules\Trace\Repositories\Dto\DynamicIndex;

use Illuminate\Support\Carbon;

readonly class TraceDynamicIndexDataDto
{
    /**
     * @param TraceDynamicIndexFieldDto[] $fields
     */
    public function __construct(
        public ?Carbon $loggedAtFrom,
        public ?Carbon $loggedAtTo,
        public array $fields
    ) {
    }
}
