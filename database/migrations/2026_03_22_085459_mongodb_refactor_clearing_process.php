<?php

use App\Services\Mongo\MongoSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    // Not Migration::$connection: that one is resolved through the database manager,
    // which has no Mongo driver. This names a `database.connections.mongodb.*` entry
    // MongoSchema reads as plain configuration.
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'traceClearingProcesses';

    // Nothing here is transactional, and the transaction the migrator would open is on
    // the default MySQL connection, which none of this touches.
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = app(MongoSchema::class);

        $schema->dropCollection($this->connectionName, $this->collectionName);

        $schema->createCollection(
            $this->connectionName,
            $this->collectionName,
            [
                'validator' => [
                    '$jsonSchema' => [
                        'bsonType'   => 'object',
                        'required'   => [
                            'clearedCollectionsCount',
                            'clearedTracesCount',
                            'error',
                            'errorTrace',
                            'clearedAt',
                        ],
                        'properties' => [
                            'clearedCollectionsCount' => [
                                'bsonType' => 'number',
                            ],
                            'clearedTracesCount'      => [
                                'bsonType' => 'number',
                            ],
                            'error'                   => [
                                'bsonType' => ['string', 'null'],
                            ],
                            'errorTrace'              => [
                                'bsonType' => ['string', 'null'],
                            ],
                            'clearedAt'               => [
                                'bsonType' => ['date', 'null'],
                            ],
                            'createdAt'               => [
                                'bsonType' => 'date',
                            ],
                            'updatedAt'               => [
                                'bsonType' => 'date',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $secondsPerHour = 60 * 60;

        $schema->createIndex(
            $this->connectionName,
            $this->collectionName,
            keys: [
                'createdAt' => 1,
            ],
            options: [
                'expireAfterSeconds' => $secondsPerHour * 12, // 12 hours
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = app(MongoSchema::class);

        $schema->dropCollection($this->connectionName, $this->collectionName);

        $schema->createCollection(
            $this->connectionName,
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

        $secondsPerHour = 60 * 60;

        $schema->createIndex(
            $this->connectionName,
            $this->collectionName,
            keys: [
                'createdAt' => 1,
            ],
            options: [
                'expireAfterSeconds' => $secondsPerHour * 24, // 24 hours
            ]
        );
    }
};
