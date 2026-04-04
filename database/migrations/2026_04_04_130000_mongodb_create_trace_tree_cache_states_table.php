<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

return new class extends Migration {
    protected $connection = 'mongodb.traces';
    protected string $collectionName = 'traceTreeCacheStates';

    public function up(): void
    {
        /** @var Connection $connection */
        $connection = DB::connection($this->connection);

        $connection->createCollection($this->collectionName);

        $collection = $connection->selectCollection($this->collectionName);

        $collection->createIndex(
            key: [
                'rootTraceId' => 1,
            ],
            options: [
                'unique' => true,
            ]
        );

        $collection->createIndex(
            key: [
                'updatedAt' => 1,
            ],
            options: [
                'expireAfterSeconds' => 60 * 60,
            ]
        );
    }

    public function down(): void
    {
        /** @var Connection $connection */
        $connection = DB::connection($this->connection);

        $connection->dropCollection($this->collectionName);
    }
};
