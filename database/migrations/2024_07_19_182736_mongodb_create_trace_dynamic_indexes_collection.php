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
                            'fieldsKey',
                            'loggedAtFrom',
                            'loggedAtTo',
                            'fields',
                            'inProcess',
                            'created',
                            'actualUntilAt',
                            'createdAt',
                        ],
                        'properties' => [
                            'indexName'     => [
                                'bsonType' => 'string',
                            ],
                            'fieldsKey'     => [
                                'bsonType' => 'string',
                            ],
                            'loggedAtFrom'  => [
                                'bsonType' => ['date', 'null'],
                            ],
                            'loggedAtTo'    => [
                                'bsonType' => ['date', 'null'],
                            ],
                            'fields'        => [
                                'bsonType' => 'array',
                            ],
                            'inProcess'     => [
                                'bsonType' => 'bool',
                            ],
                            'created'       => [
                                'bsonType' => 'bool',
                            ],
                            'actualUntilAt' => [
                                'bsonType' => 'date',
                            ],
                            'createdAt'     => [
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
                'indexName' => 1,
            ],
            [
                'unique' => true,
            ]
        );

        $collection->createIndex(
            [
                'fieldsKey'    => 1,
                'loggedAtFrom' => 1,
                'loggedAtTo'   => 1,
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
