<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

return new class extends Migration {
    protected $connection = 'mongodb.traces';
    protected string $collectionName = 'hub';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /** @var Connection $connection */
        $connection = DB::connection($this->connection);

        $connection->createCollection($this->collectionName);

        $collection = $connection->selectCollection($this->collectionName);

        $secondsPerHour = 60 * 60;

        $collection->createIndex(
            key: [
                'tid' => 1,
            ],
        );

        $collection->createIndex(
            key: [
                'sid' => 1,
                'tid' => 1,
            ],
        );

        $collection->createIndex(
            key: [
                'lat' => 1,
                '__ins' => 1,
                '__upd' => 1,
            ],
        );

        $collection->createIndex(
            key: [
                'lat' => 1,
            ],
            options: [
                'expireAfterSeconds' => $secondsPerHour, // 1 hour
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
