<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

return new class extends Migration {
    protected $connection = 'mongodb.traces';
    protected string $collectionName = 'buffer';

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
                'op' => 1,
                'cat' => 1,
            ],
        );

        $collection->createIndex(
            key: [
                'cat' => 1,
            ],
            options: [
                'expireAfterSeconds' => $secondsPerHour * 6, // 6 hours
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

        $collection->dropIndex('op_1_cat_1');
        $collection->dropIndex('cat_1');
    }
};
