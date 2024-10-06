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
                            'sid',
                            'tid',
                            'ptid',
                            'tp',
                            'tgs',
                            'dtkv',
                            'lat',
                            'cat',
                            'uat',
                        ],
                        'properties' => [
                            'sid'  => [
                                'bsonType' => 'number',
                            ],
                            'tid'  => [
                                'bsonType' => 'string',
                            ],
                            'ptid' => [
                                'bsonType' => ['string', 'null'],
                            ],
                            'tp'   => [
                                'bsonType' => 'string',
                            ],
                            'st'   => [
                                'bsonType' => 'string',
                            ],
                            'tgs'  => [
                                'bsonType' => 'array',
                            ],
                            'dt'   => [
                                'bsonType' => ['object', 'array'],
                            ],
                            'dtkv' => [
                                'bsonType' => ['array'],
                            ],
                            'dur'  => [
                                'bsonType' => ['number', 'null'],
                            ],
                            'mem'  => [
                                'bsonType' => ['number', 'null'],
                            ],
                            'cpu'  => [
                                'bsonType' => ['number', 'null'],
                            ],
                            'hpr'  => [
                                'bsonType' => ['bool'],
                            ],
                            'pr'   => [
                                'bsonType' => ['object', 'null'],
                            ],
                            'cl'   => [
                                'bsonType' => ['bool'],
                            ],
                            'lat'  => [
                                'bsonType' => 'date',
                            ],
                            'cat'  => [
                                'bsonType' => 'date',
                            ],
                            'uat'  => [
                                'bsonType' => 'date',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $collection = $connection->selectCollection($this->collectionName);

        $collection->createIndex([
            'sid' => 1,
        ]);
        $collection->createIndex([
            'tid' => 1,
        ]);
        $collection->createIndex([
            'ptid' => 1,
        ]);
        $collection->createIndex([
            'tp' => 1,
        ]);
        $collection->createIndex([
            'st' => 1,
        ]);
        $collection->createIndex([
            'tgs.nm' => 1,
        ]);
        $collection->createIndex([
            'lat' => 1,
        ]);
        $collection->createIndex([
            'dtkv.k' => 1,
        ]);
        $collection->createIndex([
            'dtkv.v' => 1,
        ]);
        $collection->createIndex([
            'dtkv.k' => 1,
            'dtkv.v' => 1,
        ]);
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
