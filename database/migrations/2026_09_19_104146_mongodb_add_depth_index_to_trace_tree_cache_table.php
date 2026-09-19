<?php

use Illuminate\Database\Migrations\Migration;
use SConcur\Features\Mongodb\Connection\Client;
use SConcur\Features\Mongodb\Connection\Database;

return new class extends Migration {
    protected string $connectionName = 'mongodb.traces';
    protected string $collectionName = 'traceTreeCache';

    public $withinTransaction = false;

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->database()->command([
            'createIndexes' => $this->collectionName,
            'indexes'       => [
                [
                    'key'  => [
                        'rootTraceId' => 1,
                        'depth'       => 1,
                    ],
                    'name' => 'rootTraceId_1_depth_1',
                ],
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->database()->command([
            'dropIndexes' => $this->collectionName,
            'index'       => 'rootTraceId_1_depth_1',
        ]);
    }

    private function database(): Database
    {
        $config = config("database.connections.$this->connectionName");

        return new Client(
            "mongodb://{$config['username']}:{$config['password']}@{$config['host']}:{$config['port']}",
            timeoutMs: $config['options']['socketTimeoutMS'] ?? null,
        )->selectDatabase($config['database']);
    }
};
