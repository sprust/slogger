<?php

namespace App\Models\Concerns;

use SConcur\Features\Mysql\Connection as SconcurConnection;

trait HasSqlSconcurConnectionTrait
{
    /**
     * @var array<class-string, SconcurConnection>
     */
    private static array $sconcurConnections = [];

    public static function sconcur(): SconcurConnection
    {
        $class = static::class;

        if (array_key_exists($class, static::$sconcurConnections)) {
            return static::$sconcurConnections[$class];
        }

        $instance = new static();

        $connectionName = $instance->getConnectionName() ?: config('database.default');

        $config = config("database.connections.$connectionName");

        $username = $config['username'];
        $password = $config['password'];
        $host     = $config['host'];
        $port     = $config['port'];
        $database = $config['database'];

        $dsn = "$username:$password@tcp($host:$port)/$database?parseTime=true";

        return static::$sconcurConnections[$class] = new SconcurConnection($dsn);
    }
}
