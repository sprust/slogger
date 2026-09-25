<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Commands;

use App\Modules\Logs\Domain\Actions\CleanLogsAction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CleanLogsCommand extends Command
{
    protected $signature = 'logs:clean';

    protected $description = 'Delete log files past their keep_days and the indexes of files that are gone';

    public function handle(CleanLogsAction $cleanLogsAction): int
    {
        $result = $cleanLogsAction->handle(Carbon::now());

        foreach ($result->deletedFiles as $path) {
            $this->components->twoColumnDetail('deleted', $path);
        }

        $this->components->info(
            sprintf(
                '%d files deleted, %d indexes deleted, %d indexes busy and left for the next run',
                count($result->deletedFiles),
                $result->deletedIndexes,
                $result->skippedIndexes
            )
        );

        return self::SUCCESS;
    }
}
