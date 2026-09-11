<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Actions;

use App\Modules\Logs\Entities\Log\LogLevelStatObject;
use App\Modules\Logs\Repositories\LogRepository;
use Illuminate\Support\Carbon;

readonly class FindLogErrorStatAction
{
    private const array ERROR_LEVELS = ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'];

    public function __construct(
        private LogRepository $logRepository
    ) {
    }

    public function handle(Carbon $since, Carbon $until): LogLevelStatObject
    {
        return $this->logRepository->findLevelStatBetween(
            since: $since,
            until: $until,
            levels: self::ERROR_LEVELS
        );
    }
}
