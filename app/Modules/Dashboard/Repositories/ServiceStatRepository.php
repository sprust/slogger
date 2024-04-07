<?php

namespace App\Modules\Dashboard\Repositories;

use App\Models\Traces\Trace;
use App\Modules\Dashboard\Repositories\Dto\ServiceStatDto;
use App\Modules\Dashboard\Repositories\Interfaces\ServiceStatRepositoryInterface;
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
                    'loggedAt' => [
                        '$gte' => new UTCDateTime($from),
                        '$lte' => new UTCDateTime($to),
                    ],
                ],
            ];

            $pipeline[] = [
                '$group' => [
                    '_id'   => [
                        'serviceId' => '$serviceId',
                        'type'      => '$type',
                        'status'    => '$status',
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
                $stats[] = new ServiceStatDto(
                    from: $from,
                    to: $to,
                    serviceId: $document->_id->serviceId,
                    type: $document->_id->type,
                    status: $document->_id->status,
                    count: $document->count
                );
            }
        }

        return $stats;
    }
}
