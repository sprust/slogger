<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

return new class extends Migration {
    protected $connection = 'mongodb.traces';
    protected string $collectionName = 'traceClearingProcesses';

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
                            'settingId',
                            'clearedCount',
                            'clearedAt',
                            'createdAt',
                            'updatedAt',
                        ],
                        'properties' => [
                            'settingId'    => [
                                'bsonType' => 'number',
                            ],
                            'clearedCount' => [
                                'bsonType' => 'number',
                            ],
                            'clearedAt'    => [
                                'bsonType' => ['date', 'null'],
                            ],
                            'createdAt'    => [
                                'bsonType' => 'date',
                            ],
                            'updatedAt'    => [
                                'bsonType' => 'date',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $collection = $connection->selectCollection($this->collectionName);

        $secondsPerHour = 60 * 60;

        $collection->createIndex(
            key: [
                'createdAt' => 1,
            ],
            options: [
                'expireAfterSeconds' => $secondsPerHour * 24, // 24 hours
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
