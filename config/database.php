<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],

        /*
         * The same MySQL server as above, reached through the SConcur SQL feature
         * instead of PDO: a statement runs in the Go extension while the calling
         * coroutine suspends, so concurrent handlers in one process no longer queue
         * behind one blocking handle. Outside a coroutine the same calls are synchronous,
         * so this is simply DB_CONNECTION — nothing picks it at runtime.
         *
         * charset, collation, timezone and strict end up in the DSN rather than in
         * SET statements after connecting — the Go driver applies them itself.
         * max_open_conns has a ceiling on purpose: each concurrent statement takes
         * its own connection, so an unbounded pool walks into max_connections.
         */
        'sconcur_mysql' => [
            'driver' => 'sconcur_mysql',
            // Same key as above so the two connections cannot drift: DatabaseManager
            // expands it through ConfigurationUrlParser before the driver sees it.
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'timeout_ms' => (int) env('SCONCUR_DB_TIMEOUT_MS', 30000),
            'max_open_conns' => (int) env('SCONCUR_DB_MAX_OPEN_CONNS', 20),
            'max_idle_conns' => (int) env('SCONCUR_DB_MAX_IDLE_CONNS', 0),
            'conn_max_lifetime_ms' => (int) env('SCONCUR_DB_CONN_MAX_LIFETIME_MS', 0),
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            // 'encrypt' => env('DB_ENCRYPT', 'yes'),
            // 'trust_server_certificate' => env('DB_TRUST_SERVER_CERTIFICATE', 'false'),
        ],

        /*
         * Not connections of the database manager: there is no Mongo driver registered
         * for it, and DB::connection() on one of these would fail. They are read as plain
         * configuration by App\Services\Mongo\MongoConnectionFactory, which builds the
         * URI of the non-blocking SConcur client every read and write goes through.
         *
         * The nesting is what AbstractMongoModel names in $connection, as `mongodb.<key>`.
         */
        'mongodb' => [
            'traces' => [
                'host'     => env('MONGO_HOST'),
                'port'     => env('MONGO_PORT'),
                'username' => env('MONGO_ADMIN_USERNAME'),
                'password' => env('MONGO_ADMIN_PASSWORD'),
                'database' => env('MONGO_DATABASE_TRACES',  'traces'),
                'options'  => [
                    'appname'    => env('APP_NAME'),
                    'authSource' => env('MONGO_DATABASE_ADMIN', 'admin'),
                ],
            ],
            'tracesPeriodic' => [
                'host'     => env('MONGO_HOST'),
                'port'     => env('MONGO_PORT'),
                'username' => env('MONGO_ADMIN_USERNAME'),
                'password' => env('MONGO_ADMIN_PASSWORD'),
                'database' => env('MONGO_DATABASE_TRACES_PERIODIC',  'tracesPeriodic'),
                'options'  => [
                    'appname'    => env('APP_NAME'),
                    'authSource' => env('MONGO_DATABASE_ADMIN', 'admin'),
                    'socketTimeoutMS'=> 1200000 // 20 minutes
                ],
            ],
            'logs' => [
                'host'     => env('MONGO_HOST'),
                'port'     => env('MONGO_PORT'),
                'username' => env('MONGO_ADMIN_USERNAME'),
                'password' => env('MONGO_ADMIN_PASSWORD'),
                'database' => env('MONGO_DATABASE_LOGS',  'logs'),
                'options'  => [
                    'appname'    => env('APP_NAME'),
                    'authSource' => env('MONGO_DATABASE_ADMIN', 'admin'),
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

];
