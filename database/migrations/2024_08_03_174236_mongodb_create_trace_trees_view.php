<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;

return new class extends Migration {
    protected $connection = 'mongodb.traces';
    protected string $viewName = 'traceTreesView';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        /** @var Connection $connection */
        $connection = DB::connection($this->connection);

        $connection->command([
            'create'   => $this->viewName,
            'viewOn'   => 'traces',
            'pipeline' => [
                [
                    '$project' => [
                        'tid'  => 1,
                        'ptid' => 1,
                    ],
                ],
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        /** @var Connection $connection */
        $connection = DB::connection($this->connection);

        $connection->selectCollection($this->viewName)->drop();
    }
};
