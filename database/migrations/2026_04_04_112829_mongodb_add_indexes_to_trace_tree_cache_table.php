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
                'traceId'     => 1,
            ]
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app(MongoSchema::class)->dropIndex(
            $this->connectionName,
            $this->collectionName,
            'rootTraceId_1_traceId_1'
        );
    }
};
