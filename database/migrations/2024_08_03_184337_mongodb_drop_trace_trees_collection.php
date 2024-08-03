<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

return new class extends Migration
{
    protected $connection = 'mongodb.traces';
    protected string $collectionName = 'traceTrees';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /** @var Connection $connection */
        $connection = DB::connection($this->connection);

        $collection = $connection->selectCollection($this->collectionName);

        $collection->dropIndexes();
        $collection->drop();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
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
                            'traceId',
                            'parentTraceId',
                            'loggedAt',
                            'createdAt',
                        ],
                        'properties' => [
                            'traceId'       => [
                                'bsonType' => 'string',
                            ],
                            'parentTraceId' => [
                                'bsonType' => ['string', 'null'],
                            ],
                            'loggedAt'      => [
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
                'traceId' => 1,
            ]
        );
        $collection->createIndex(
            [
                'parentTraceId' => 1,
            ]
        );
        $collection->createIndex(
            [
                'traceId'       => 1,
                'parentTraceId' => 1,
            ],
            [
                'unique' => true,
            ]
        );
    }
};
