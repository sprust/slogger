<?php

use App\Services\Mongo\MongoSchema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    // Not Migration::$connection: that one is resolved through the database manager,
    // which has no Mongo driver. This names a `database.connections.mongodb.*` entry
    // MongoSchema reads as plain configuration.
    protected string $connectionName = 'mongodb.logs';
    protected string $collectionName = 'logs';

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

        $secondsPerHour = 60 * 60;

        $schema->createIndex(
            $this->connectionName,
            $this->collectionName,
            keys: [
                'loggedAt' => 1,
            ],
            options: [
                'expireAfterSeconds' => $secondsPerHour * 36, // 3 days
            ]
        );

        $schema->createIndex(
            $this->connectionName,
            $this->collectionName,
            keys: [
                'loggedAt' => 1,
                'message'  => 'text',
                'level'    => 1,
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
