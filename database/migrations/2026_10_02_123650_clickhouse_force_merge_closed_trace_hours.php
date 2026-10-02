<?php

use App\Services\Clickhouse\ClickhouseClient;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public $withinTransaction = false;

    public function up(): void
    {
        // ClickHouse merges an hour into one part once all its parts are an hour old, which
        // leaves one row per trace there; a late trace starts the hour's wait again.
        app(ClickhouseClient::class)->command(
            sql: 'ALTER TABLE traces MODIFY SETTING '
            . 'min_age_to_force_merge_seconds = 3600, min_age_to_force_merge_on_partition_only = 1',
            queryIdPrefix: 'migrate'
        );
    }

    public function down(): void
    {
        app(ClickhouseClient::class)->command(
            sql: 'ALTER TABLE traces RESET SETTING min_age_to_force_merge_seconds, min_age_to_force_merge_on_partition_only',
            queryIdPrefix: 'migrate'
        );
    }
};
