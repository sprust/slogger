<?php

namespace App\Console\Commands\Migrate;

use App\Services\Mongo\MongoConnectionFactory;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Console\Migrations\FreshCommand;

class MigrateFreshCommand extends Command
{
    use ConfirmableTrait;

    protected $name = 'migrate:fresh';

    public function handle(MongoConnectionFactory $connections): int
    {
        if (!$this->confirmToProceed()) {
            return 1;
        }

        $this->components->info('Dropping all mongodb tables');

        foreach ($connections->connectionNames() as $connectionName) {
            $database     = $connections->database($connectionName);
            $databaseName = $connections->databaseName($connectionName);

            foreach ($database->listCollections() as $collectionName) {
                if ($collectionName === 'system.views') {
                    continue;
                }

                // Dropping the collection takes its indexes with it, so there is nothing
                // to drop separately first.
                $this->components->task(
                    "Drop $databaseName.$collectionName",
                    static function () use ($database, $collectionName): bool {
                        $database->selectCollection($collectionName)->drop();

                        return true;
                    }
                );
            }
        }

        return $this->call(FreshCommand::class);
    }
}
