<?php

namespace App\Services\Mongo;

use Illuminate\Foundation\Application;
use MongoDB\Laravel\Connection;

class MongoConnectorService
{
    public static function register(Application $app): void
    {
        $app->resolving('db', function ($db) {
            $db->extend('mongodb', function ($config, $name) {
                $config['name'] = $name;

                $customConnectorClass = $config['connector'] ?? null;

                if ($customConnectorClass) {
                    return new $customConnectorClass($config);
                }

                return new Connection($config);
            });
        });

    }
}
