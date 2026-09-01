<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver'       => 'database',
            'table'        => 'jobs',
            'queue'        => 'default',
            'retry_after'  => 90,
            'after_commit' => false,
        ],

        'beanstalkd' => [
            'driver'       => 'beanstalkd',
            'host'         => 'localhost',
            'queue'        => 'default',
            'retry_after'  => 90,
            'block_for'    => 0,
            'after_commit' => false,
        ],

        'sqs' => [
            'driver'       => 'sqs',
            'key'          => env('AWS_ACCESS_KEY_ID'),
            'secret'       => env('AWS_SECRET_ACCESS_KEY'),
            'prefix'       => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue'        => env('SQS_QUEUE', 'default'),
            'suffix'       => env('SQS_SUFFIX'),
            'region'       => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'after_commit' => false,
        ],

        'redis' => [
            'driver'       => 'redis',
            'connection'   => 'default',
            'queue'        => env('REDIS_QUEUE', 'default'),
            'retry_after'  => 90,
            'block_for'    => null,
            'after_commit' => false,
        ],

        /*
         * The SConcur AMQP transport: the consumer pool reads it with coroutines under
         * the sconcur master instead of one blocking queue:work per worker. It is the
         * only way to RabbitMQ here — QUEUE_CONNECTION names it, and so does the slogger
         * dispatcher — so this is where a job goes unless it says otherwise.
         *
         * The wire format is the one vladimir-yuldashev/laravel-queue-rabbitmq writes:
         * the same body, the same message properties, the same laravel.attempts header.
         * The package is gone, but a message it left in a queue still reads.
         */
        'sconcur_rabbitmq' => [
            'driver'    => 'sconcur_rabbitmq',
            'queue'     => env('RABBITMQ_QUEUE', 'default'),
            'dsn'       => env(
                'SCONCUR_RABBITMQ_DSN',
                sprintf(
                    'amqp://%s:%s@%s:%s/%s',
                    env('RABBITMQ_USER', 'guest'),
                    env('RABBITMQ_PASSWORD', 'guest'),
                    env('RABBITMQ_HOST', '127.0.0.1'),
                    env('RABBITMQ_PORT', 5672),
                    rawurlencode((string) env('RABBITMQ_VHOST', '/')),
                ),
            ),
            // The wait queues a later() or a release() may address; a delay is rounded
            // up to the nearest of these. Declared by sconcur:rabbitmq:declare.
            'after_commit' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Job Batching
    |--------------------------------------------------------------------------
    |
    | The following options configure the database and table that store job
    | batching information. These options can be updated to any database
    | connection and table which has been defined by your application.
    |
    */

    'batching' => [
        // null follows database.default, so a coroutine process writes these
        // through the coroutine-safe connection instead of blocking PDO.
        'database' => null,
        'table'    => 'job_batches',
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'driver'   => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        // null follows database.default, so a coroutine process writes these
        // through the coroutine-safe connection instead of blocking PDO.
        'database' => null,
        'table'    => 'failed_jobs',
    ],
];
