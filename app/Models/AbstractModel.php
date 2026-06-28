<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SConcur\Features\Mysql\Connection as SconcurConnection;

abstract class AbstractModel extends Model
{
    /**
     * @var array<class-string<AbstractModel>, SconcurConnection>
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
