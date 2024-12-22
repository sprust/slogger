<?php

declare(strict_types=1);

namespace App\Modules\Logs\Entities\Log;

use App\Modules\Common\Entities\PaginationInfoObject;

readonly class LogsPaginationObject
{
    /**
     * @param LogObject[] $items
     */
    public function __construct(
        public array $items,
        public PaginationInfoObject $paginationInfo
    ) {
    }
}
