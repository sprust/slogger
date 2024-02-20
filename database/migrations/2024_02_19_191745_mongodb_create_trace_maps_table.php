<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

return new class extends Migration {
    protected $connection = 'mongodb.traces';
    protected string $collectionName = 'traceMaps';

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
                            'traceId',
                            'traceIds',
                        ],
                        'properties' => [
                            'traceId'  => [
                                'bsonType' => 'string',
                            ],
                            'traceIds' => [
                                'bsonType' => ['array'],
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
