<?php

namespace App\Console\Commands\Migrate;

use Illuminate\Database\Console\Migrations\FreshCommand;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

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

                $collection = $connection->selectCollection($collectionName);

                $this->components->task(
                    "Drop $databaseName.$collectionName",
                    function () use ($collection) {
                        $collection->dropIndexes();
                        $collection->drop();

                        return true;
                    }
                );
            }
        }

        return parent::handle();
    }
}
