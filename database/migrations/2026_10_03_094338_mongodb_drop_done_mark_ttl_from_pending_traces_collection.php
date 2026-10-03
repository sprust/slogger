<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Collection;

return new class extends Migration {
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'pendingTraces';

    public $withinTransaction = false;

    private const string TTL_INDEX_NAME = 'dat_1';

    private const int TTL_SECONDS = 60 * 60;

    public function up(): void
    {
        $collection = $this->collection();

        $collection->updateMany(
            ['dat' => ['$exists' => true]],
            ['$rename' => ['dat' => 'uat']],
        );

        $indexNames = array_column($collection->listIndexes(), 'name');

        if (in_array(self::TTL_INDEX_NAME, $indexNames, true)) {
            $collection->dropIndex(self::TTL_INDEX_NAME);
        }
    }

    public function down(): void
    {
        $collection = $this->collection();

        $collection->updateMany(
            ['dn' => true],
            ['$rename' => ['uat' => 'dat']],
        );

        $collection->database->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    'key'                => ['dat' => 1],
                    'name'               => self::TTL_INDEX_NAME,
                    'expireAfterSeconds' => self::TTL_SECONDS,
                ],
            ],
        ]);
    }

    private function collection(): Collection
    {
        $config = config("database.connections.$this->connectionName");

        return new Client(
            "mongodb://{$config['username']}:{$config['password']}@{$config['host']}:{$config['port']}",
            timeoutMs: $config['options']['socketTimeoutMS'] ?? null,
        )->selectDatabase($config['database'])->selectCollection($this->collectionName);
    }
};
