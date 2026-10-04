<?php

use App\Services\Clickhouse\ClickhouseClient;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public $withinTransaction = false;

    public function up(): void
    {
        app(ClickhouseClient::class)->command(
            sql: 'ALTER TABLE traces ADD COLUMN IF NOT EXISTS pid Nullable(UInt32) AFTER cpu',
            queryIdPrefix: 'migrate'
        );
    }

    public function down(): void
    {
        app(ClickhouseClient::class)->command(
            sql: 'ALTER TABLE traces DROP COLUMN IF EXISTS pid',
            queryIdPrefix: 'migrate'
        );
    }
};
