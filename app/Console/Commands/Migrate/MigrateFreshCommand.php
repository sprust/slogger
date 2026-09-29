<?php

namespace App\Console\Commands\Migrate;

use App\Services\Clickhouse\ClickhouseClient;
use App\Services\Clickhouse\ClickhouseQueryException;
use App\Services\Mongo\MongoConnectionFactory;
use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Illuminate\Database\Console\Migrations\FreshCommand;

class MigrateFreshCommand extends Command
{
    use ConfirmableTrait;

    protected $name = 'migrate:fresh';

    /**
     * @throws ClickhouseQueryException
     */
    public function handle(MongoConnectionFactory $connections, ClickhouseClient $clickhouse): int
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

        // The ClickHouse tables are created by migrations too: left in place, they would
        // outlive the migrations table that says they exist.
        $this->components->info('Dropping all clickhouse tables');

        $tables = $clickhouse->select(
            sql: 'SELECT name FROM system.tables WHERE database = currentDatabase()',
            queryIdPrefix: 'migrate'
        );

        foreach (array_column($tables, 'name') as $tableName) {
            $this->components->task(
                "Drop clickhouse.$tableName",
                static function () use ($clickhouse, $tableName): bool {
                    $clickhouse->command(
                        sql: 'DROP TABLE IF EXISTS {table:Identifier}',
                        params: ['table' => $tableName],
                        queryIdPrefix: 'migrate'
                    );

                    return true;
                }
            );
        }

        return $this->call(FreshCommand::class);
    }
}
