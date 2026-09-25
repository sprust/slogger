<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Actions;

use App\Modules\Logs\Domain\Services\Errors\LogErrorStatCounter;
use App\Modules\Logs\Entities\Log\LogLevelStatObject;
use App\Modules\Logs\Enums\LaravelLogLevelEnum;
use App\Modules\Logs\Enums\LogTypeEnum;
use Illuminate\Support\Carbon;

readonly class FindLogErrorStatAction
{
    public function __construct(
        private LogErrorStatCounter $logErrorStatCounter
    ) {
    }

    public function handle(Carbon $since, Carbon $until): LogLevelStatObject
    {
        return $this->logErrorStatCounter->count(
            type: LogTypeEnum::Laravel,
            levels: [
                LaravelLogLevelEnum::Error->value,
                LaravelLogLevelEnum::Critical->value,
                LaravelLogLevelEnum::Alert->value,
                LaravelLogLevelEnum::Emergency->value,
            ],
            since: $since,
            until: $until
        );
    }
}
