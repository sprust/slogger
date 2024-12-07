<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

return new class extends Migration {
    protected $connection = 'mongodb.traces';
    protected string $collectionName = 'traceDynamicIndexes';

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
                            'indexName',
                            'name',
                            'collectionNames',
                            'fields',
                            'inProcess',
                            'created',
                            'actualUntilAt',
                            'createdAt',
                        ],
                        'properties' => [
                            'name'            => [
                                'bsonType' => 'string',
                            ],
                            'indexName'       => [
                                'bsonType' => 'string',
                            ],
                            'collectionNames' => [
                                'bsonType' => 'array',
                            ],
                            'fields'          => [
                                'bsonType' => 'array',
                            ],
                            'inProcess'       => [
                                'bsonType' => 'bool',
                            ],
                            'created'         => [
                                'bsonType' => 'bool',
                            ],
                            'actualUntilAt'   => [
                                'bsonType' => 'date',
                            ],
                            'createdAt'       => [
                                'bsonType' => 'date',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $collection = $connection->selectCollection($this->collectionName);

        $collection->createIndex(
            [
                'name' => 1,
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
