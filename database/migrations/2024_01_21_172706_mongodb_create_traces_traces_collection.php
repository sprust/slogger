<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

return new class extends Migration {
    protected $connection = 'mongodb.traces';
    protected string $collectionName = 'traces';

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
                        'bsonType'   => 'object',
                        'required'   => [
                            'serviceId',
                            'traceId',
                            'parentTraceId',
                            'type',
                            'tags',
                            'data',
                            'loggedAt',
                            'createdAt',
                            'updatedAt',
                        ],
                        'properties' => [
                            'serviceId'       => [
                                'bsonType' => 'number',
                            ],
                            'traceId'       => [
                                'bsonType' => 'string',
                            ],
                            'parentTraceId' => [
                                'bsonType' => ['string', 'null'],
                            ],
                            'type'          => [
                                'bsonType' => 'string',
                            ],
                            'tags'          => [
                                'bsonType' => 'array',
                            ],
                            'data'          => [
                                'bsonType' => 'array',
                            ],
                            'loggedAt'      => [
                                'bsonType' => 'date',
                            ],
                            'createdAt'     => [
                                'bsonType' => 'date',
                            ],
                            'updatedAt'     => [
                                'bsonType' => 'date',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $connection->selectCollection($this->collectionName)
            ->createIndex(
                [
                    'traceId' => 1,
                ],
                [
                    'unique' => true,
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
