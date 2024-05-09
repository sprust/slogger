<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

return new class extends Migration {
    protected $connection = 'mongodb.traces';
    protected string $collectionName = 'traceMetrics';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /** @var Connection $connection */
        $connection = DB::connection($this->connection);

        $connection->createCollection(
            $this->collectionName,
            [
                'validator' => [
                    '$jsonSchema' => [
                        'bsonType' => 'object',
                        'required' => [
                            'serviceId',
                            'type',
                            'status',
                            'timestamp',
                            'count',
                        ],
                        'properties' => [
                            'serviceId' => [
                                'bsonType' => 'number',
                            ],
                            'type'      => [
                                'bsonType' => 'string',
                            ],
                            'status'    => [
                                'bsonType' => 'string',
                            ],
                            'timestamp' => [
                                'bsonType' => 'date',
                            ],
                            'count'     => [
                                'bsonType' => 'number',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $collection = $connection->selectCollection($this->collectionName);

        $collection->createIndex(
            [
                'serviceId' => 1,
            ]
        );
        $collection->createIndex(
            [
                'type' => 1,
            ]
        );
        $collection->createIndex(
            [
                'status' => 1,
            ]
        );
        $collection->createIndex(
            [
                'timestamp' => 1,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /** @var Connection $connection */
        $connection = DB::connection($this->connection);

        $collection = $connection->selectCollection($this->collectionName);

        $collection->dropIndexes();
        $collection->drop();
    }
};
