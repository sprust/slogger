<?php

namespace App\Modules\ProjectLogs\RawLogs;

use App\Models\ProjectLogs\ProjectRawLog;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Connection;
use Throwable;

class ProjectRawLogsMigration
{
    /**
     * @throws Throwable
     */
    public function up(): void
    {
        /** @var Connection $connection */
        $connection = DB::connection('mongodb.projects');

        $connection->createCollection(
            app(ProjectRawLog::class)->getTable(),
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
                            'loggedAt',
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
                            'loggedAt'          => [
                                'bsonType' => 'date',
                            ],
                            'createdAt'          => [
                                'bsonType' => 'date',
                            ],
                        ],
                    ],
                ],
            ]
        );

        ProjectRawLog::collection()->createIndex(
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
        ProjectRawLog::collection()->dropIndexes();
        ProjectRawLog::collection()->drop();
    }
}
