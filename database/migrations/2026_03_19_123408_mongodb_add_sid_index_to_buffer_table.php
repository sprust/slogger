<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

return new class extends Migration
{
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

        $collection->createIndex(
            key: [
                'sid' => 1,
                'cat' => 1,
            ],
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

        $collection->dropIndex('sid_1_cat_1');
    }
};
