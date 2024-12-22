<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Commands;

use App\Modules\Trace\Contracts\Actions\Mutations\DeleteOldEmptyCollectionsActionInterface;
use Illuminate\Console\Command;

class DeleteOldEmptyCollectionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trace:delete-old-empty-collections';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete old empty collections';

    /**
     * Execute the console command.
     */
    public function handle(DeleteOldEmptyCollectionsActionInterface $action): int
    {
        $action->handle();

        return self::SUCCESS;
    }
}
