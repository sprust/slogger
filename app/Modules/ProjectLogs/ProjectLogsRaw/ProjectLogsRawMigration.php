<?php

namespace App\Modules\ProjectLogs\ProjectLogsRaw;

use App\Models\Logs\RawLog;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;
use Throwable;

class ProjectLogsRawMigration
{
    /**
     * @throws Throwable
     */
    public function up(): void
    {
        /** @var Connection $connection */
        $connection = DB::connection('mongodb.projects');

        $connection->createCollection(
            app(RawLog::class)->getTable(),
            [
                'validator' => [
                    '$jsonSchema' => [
                        'bsonType'   => 'object',
                        'required'   => [
                            'service',
                            'trackId',
                            'parentTrackId',
                            'type',
                            'tags',
                            'data',
                        ],
                        'properties' => [
                            'service'       => [
                                'bsonType' => 'string',
                            ],
                            'trackId'       => [
                                'bsonType' => 'string',
                            ],
                            'parentTrackId' => [
                                'bsonType' => ['string', 'null'],
                            ],
                            'type'          => [
                                'bsonType' => 'string',
                            ],
                            'tags'          => [
                                'bsonType' => 'array',
                            ],
                            'data'          => [
                                'bsonType' => 'array',
                            ],
                        ],
                    ],
                ],
            ]
        );

        RawLog::collection()->createIndex(
            [
                'trackId' => 1,
            ],
            [
                'unique' => true,
            ]
        );
    }

    public function down(): void
    {
        RawLog::collection()->dropIndexes();
        RawLog::collection()->drop();
    }
}
