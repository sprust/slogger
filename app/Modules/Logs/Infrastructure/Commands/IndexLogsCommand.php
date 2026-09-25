<?php

declare(strict_types=1);

namespace App\Modules\Logs\Infrastructure\Commands;

use App\Modules\Logs\Domain\Actions\IndexLogsAction;
use Illuminate\Console\Command;

class IndexLogsCommand extends Command
{
    protected $signature = 'logs:index';

    protected $description = 'Bring the indexes of every log file up to date';

    public function handle(IndexLogsAction $indexLogsAction): int
    {
        $batch = $indexLogsAction->handle();

        $this->components->info(
            sprintf(
                '%d files indexed, %d of %d bytes, %d missing%s',
                count($batch->files),
                $batch->indexedBytes,
                $batch->totalBytes,
                count($batch->missingFileIds),
                $batch->indexing ? ', some are being indexed elsewhere' : ''
            )
        );

        return self::SUCCESS;
    }
}
