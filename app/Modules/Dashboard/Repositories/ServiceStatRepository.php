<?php

namespace App\Modules\Dashboard\Repositories;

use App\Models\Traces\Trace;
use App\Modules\Dashboard\Contracts\Repositories\ServiceStatRepositoryInterface;
use App\Modules\Dashboard\Entities\ServiceStatRawObject;
use MongoDB\BSON\UTCDateTime;

readonly class ServiceStatRepository implements ServiceStatRepositoryInterface
{
    public function find(): array
    {
        $periods = [
            [
                now('UTC'),
                now('UTC')->startOfHour(),
            ],
            [
                now('UTC')->subHour()->endOfHour(),
                now('UTC')->subHour()->startOfHour(),
            ],
            [
                now('UTC')->subHours(2)->endOfHour(),
                now('UTC')->subHours(2)->startOfHour(),
            ],
            [
                now('UTC')->subHours(3)->endOfHour(),
                now('UTC')->subHours(3)->startOfHour(),
            ],
            [
                now('UTC')->subHours(4)->endOfHour(),
                now('UTC')->subHours(4)->startOfHour(),
            ],
            [
                now('UTC')->subHours(5)->endOfHour(),
                now('UTC')->subHours(5)->startOfHour()->subDay(),
            ],
            [
                now('UTC')->subHours(6)->endOfHour()->subDay(),
                now('UTC')->subHours(6)->startOfHour()->subDays(2),
            ],
            [
                now('UTC')->subHours(7)->endOfHour()->subDays(4),
                now('UTC')->subHours(7)->startOfHour()->subDays(4)->subDays(7),
            ],
        ];

        $stats = [];

        foreach ($periods as $period) {
            $pipeline = [];

            $from = $period[1];
            $to   = $period[0];

            $pipeline[] = [
                '$match' => [
                    'lat' => [
                        '$gte' => new UTCDateTime($from),
                        '$lte' => new UTCDateTime($to),
                    ],
                ],
            ];

            $pipeline[] = [
                '$group' => [
                    '_id'   => [
                        'serviceId' => '$sid',
                        'type'      => '$tp',
                        'status'    => '$st',
                    ],
                    'count' => [
                        '$sum' => 1,
                    ],
                ],
            ];

            $pipeline[] = [
                '$sort' => [
                    '_id.serviceId' => 1,
                    '_id.type'      => 1,
                    '_id.status'    => 1,
                ],
            ];

            $documents = Trace::collection()->aggregate($pipeline)->toArray();

            foreach ($documents as $document) {
                $stats[] = new ServiceStatRawObject(
                    serviceId: $document->_id->serviceId,
                    from: $from,
                    to: $to,
                    type: $document->_id->type,
                    status: $document->_id->status,
                    count: $document->count
                );
            }
        }

        return $stats;
    }
}
