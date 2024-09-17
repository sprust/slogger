<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

return new class extends Migration
{
    protected $connection = 'mongodb.traces';
    protected string $collectionName = 'traceAdminStores';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /** @var Connection $connection */
        $connection = DB::connection($this->connection);

        $connection->createCollection($this->collectionName);

        $collection = $connection->selectCollection($this->collectionName);

        $secondsPerDay = 60 * 60 * 24;

        $collection->createIndex(
            key: [
                'usedAt' => 1,
            ],
            options: [
                'expireAfterSeconds' => $secondsPerDay * 30, // 30 days
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
