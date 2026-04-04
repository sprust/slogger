<?php

declare(strict_types=1);

namespace App\Modules\Trace\Entities\Trace\Tree;

use App\Modules\Trace\Enums\TraceTreeCacheStateStatusEnum;
use Illuminate\Support\Carbon;

readonly class TraceTreeCacheStateObject
{
    public function __construct(
        public string $rootTraceId,
        public string $version,
        public TraceTreeCacheStateStatusEnum $status,
        public int $count,
        public ?string $error,
        public ?Carbon $startedAt,
        public ?Carbon $finishedAt,
        public Carbon $createdAt,
        public Carbon $updatedAt,
    ) {
    }
}
