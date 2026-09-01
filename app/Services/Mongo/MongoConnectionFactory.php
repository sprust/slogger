<?php

declare(strict_types=1);

namespace App\Services\Mongo;

use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

/**
 * Builds a SConcur Mongo client out of a `database.connections.mongodb.*` entry.
 *
 * Those entries are read as plain configuration, not resolved through the database
 * manager: the ORM has no Mongo driver here, and every read and write goes through the
 * SConcur feature instead. This is the one place that knows how a config entry becomes a
 * connection URI, so the four callers that need one cannot drift apart.
 */
class MongoConnectionFactory
{
    /**
     * The pool ceiling the application's collections are read through.
     *
     * Not applied everywhere: the tracesPeriodic database is opened without it, as it was
     * before this factory existed. Its shard collections are queried in parallel through
     * a WaitGroup, and capping the pool there would serialise the fan-out.
     */
    public const array DEFAULT_URI_OPTIONS = [
        'maxPoolSize'    => 20,
        'maxIdleTimeMS'  => 30000,
    ];

    /**
     * @param array<string, int|string> $uriOptions
     */
    public function client(string $connectionName, array $uriOptions = self::DEFAULT_URI_OPTIONS): Client
    {
        $config = $this->config($connectionName);

        $username = $config['username'];
        $password = $config['password'];
        $host     = $config['host'];
        $port     = $config['port'];

        $uri = "mongodb://$username:$password@$host:$port";

        if ($uriOptions) {
            $uri .= '/?' . http_build_query($uriOptions);
        }

        return new Client($uri, timeoutMs: $config['options']['socketTimeoutMS'] ?? null);
    }

    /**
     * @param array<string, int|string> $uriOptions
     */
    public function database(string $connectionName, array $uriOptions = self::DEFAULT_URI_OPTIONS): Database
    {
        return $this->client($connectionName, $uriOptions)
            ->selectDatabase($this->databaseName($connectionName));
    }

    public function databaseName(string $connectionName): string
    {
        return $this->config($connectionName)['database'];
    }

    /**
     * The names of the configured mongodb connections, as `mongodb.<name>`.
     *
     * @return string[]
     */
    public function connectionNames(): array
    {
        return array_map(
            static fn(int|string $name): string => "mongodb.$name",
            array_keys(config('database.connections.mongodb'))
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function config(string $connectionName): array
    {
        return config("database.connections.$connectionName");
    }
}
