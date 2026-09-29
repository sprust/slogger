<?php

declare(strict_types=1);

namespace App\Console\Commands\Migrate;

use App\Services\Clickhouse\ClickhouseClient;
use App\Services\Clickhouse\ClickhouseQueryException;
use Illuminate\Console\Command;

/**
 * Applies database/clickhouse/*.sql in the order of their names, each one once.
 *
 * One file holds one statement: the HTTP interface runs a single statement per request.
 * What has been applied is kept in `schema_migrations` of ClickHouse itself, so the
 * database that holds the tables also knows which of them it has.
 */
class ClickhouseMigrateCommand extends Command
{
    protected $signature = 'clickhouse:migrate';

    protected $description = 'Apply the ClickHouse migrations of database/clickhouse';

    /**
     * @throws ClickhouseQueryException
     */
    public function handle(ClickhouseClient $client): int
    {
        $client->command(
            sql: 'CREATE TABLE IF NOT EXISTS schema_migrations (version String, applied_at DateTime) '
            . 'ENGINE = MergeTree ORDER BY version',
            queryIdPrefix: 'migrate'
        );

        $applied = array_column(
            $client->select('SELECT version FROM schema_migrations', queryIdPrefix: 'migrate'),
            'version'
        );

        $files = glob(database_path('clickhouse/*.sql')) ?: [];

        sort($files);

        $pending = array_filter(
            $files,
            static fn(string $file): bool => !in_array(basename($file, '.sql'), $applied, true)
        );

        if ($pending === []) {
            $this->components->info('Nothing to migrate');

            return self::SUCCESS;
        }

        foreach ($pending as $file) {
            $version = basename($file, '.sql');

            $this->components->task(
                $version,
                static function () use ($client, $file, $version): bool {
                    $client->command(
                        sql: rtrim(trim((string) file_get_contents($file)), ';'),
                        queryIdPrefix: 'migrate'
                    );

                    $client->command(
                        sql: 'INSERT INTO schema_migrations (version, applied_at) VALUES ({version:String}, now())',
                        params: ['version' => $version],
                        queryIdPrefix: 'migrate'
                    );

                    return true;
                }
            );
        }

        return self::SUCCESS;
    }
}
