<?php

declare(strict_types=1);

namespace App\Modules\Logs\Domain\Actions;

use App\Modules\Logs\Domain\Services\Files\LogFileFinder;
use App\Modules\Logs\Domain\Services\Index\LogIndexBatchRefresher;
use App\Modules\Logs\Entities\Index\LogIndexBatchObject;

readonly class IndexLogsAction
{
    public function __construct(
        private LogFileFinder $logFileFinder,
        private LogIndexBatchRefresher $logIndexBatchRefresher
    ) {
    }

    public function handle(): LogIndexBatchObject
    {
        return $this->logIndexBatchRefresher->refresh(
            files: $this->logFileFinder->findAll(),
            timeBudgetMs: 0,
            concurrency: max(1, (int) config('module-logs.search.concurrency'))
        );
    }
}
