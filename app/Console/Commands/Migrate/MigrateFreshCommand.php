<?php

namespace App\Console\Commands\Migrate;

use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;
use Throwable;

class MigrateFreshCommand extends FreshCommand
{
    public function handle()
    {
        if (!$this->confirmToProceed()) {
            return 1;
        }

        $this->components->info('Dropping all mongodb tables');

        foreach (config('database.connections.mongodb') as $connectionName => $databaseConfig) {
            $databaseName = $databaseConfig['database'];

            /** @var Connection $connection */
            $connection = DB::connection("mongodb.$connectionName");

            $collectionNames = iterator_to_array($connection->listCollectionNames());

            for ($index = 0; $index < count($collectionNames); $index++) {
                $collectionName = $collectionNames[$index];

                if ($collectionName === 'system.views') {
                    continue;
                }

                $collection = $connection->selectCollection($collectionName);

                $indexDeletingException = null;

                $this->components->task(
                    "Drop $databaseName.$collectionName",
                    function () use ($collection, &$indexDeletingException) {
                        try {
                            $collection->dropIndexes();
                        } catch (Throwable $indexDeletingException) {
                        }

                        $collection->drop();

                        return true;
                    }
                );

                if ($indexDeletingException) {
                    $this->components->warn(
                        "Indexes deleting error: {$indexDeletingException->getMessage()}"
                    );
                }
            }
        }

        return parent::handle();
    }
}
