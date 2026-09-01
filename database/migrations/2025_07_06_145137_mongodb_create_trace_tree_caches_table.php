<?php

use App\Services\Mongo\MongoSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    // Not Migration::$connection: that one is resolved through the database manager,
    // which has no Mongo driver. This names a `database.connections.mongodb.*` entry
    // MongoSchema reads as plain configuration.
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'traceTreeCache';

    // Nothing here is transactional, and the transaction the migrator would open is on
    // the default MySQL connection, which none of this touches.
    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = app(MongoSchema::class);

        $schema->createCollection($this->connectionName, $this->collectionName);

        $schema->createIndex(
            $this->connectionName,
            $this->collectionName,
            keys: [
                'rootTraceId' => 1,
            ]
        );

        $schema->createIndex(
            $this->connectionName,
            $this->collectionName,
            keys: [
                'createdAt' => 1,
            ],
            options: [
                'expireAfterSeconds' => 60 * 60, // 1 hour
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = app(MongoSchema::class);

        $schema->dropIndexes($this->connectionName, $this->collectionName);
        $schema->dropCollection($this->connectionName, $this->collectionName);
    }
};
