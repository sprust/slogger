<?php

declare(strict_types=1);

namespace App\Modules\Logs\Repositories;

use App\Models\Logs\Log;
use App\Modules\Common\Entities\PaginationInfoObject;
use App\Modules\Logs\Contracts\Repositories\LogRepositoryInterface;
use App\Modules\Logs\Entities\Log\LogObject;
use App\Modules\Logs\Entities\Log\LogsPaginationObject;
use App\Modules\Logs\Parameters\CreateLogParameters;
use App\Modules\Logs\Parameters\FindLogsParameters;

readonly class LogRepository implements LogRepositoryInterface
{
    public function create(CreateLogParameters $parameters): string
    {
        $log = new Log();

        $log->level    = $parameters->level;
        $log->message  = $parameters->message;
        $log->context  = $parameters->context;
        $log->channel  = $parameters->channel;
        $log->loggedAt = $parameters->loggedAt;

        $log->save();

        return $log->_id;
    }

    public function paginate(int $page, int $perPage, FindLogsParameters $parameters): LogsPaginationObject
    {
        $builder = Log::query();

        if ($parameters->searchQuery) {
            $builder->where('message', 'like', "%{$parameters->searchQuery}%");
        }

        if ($parameters->level) {
            $builder->where('level', $parameters->level);
        }

        $total = $builder->count();

        $items = $builder
            ->orderBy('loggedAt', 'desc')
            ->forPage(page: $page, perPage: $perPage)
            ->get()
            ->map(static function (Log $log) {
                return new LogObject(
                    level: $log->level,
                    message: $log->message,
                    context: $log->context,
                    channel: $log->channel,
                    loggedAt: $log->loggedAt
                );
            })
            ->all();

        return new LogsPaginationObject(
            items: $items,
            paginationInfo: new PaginationInfoObject(
                total: $total,
                perPage: $perPage,
                currentPage: $page
            )
        );
    }
}
