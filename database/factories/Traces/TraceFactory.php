<?php

namespace Database\Factories\Traces;

use App\Models\Services\Service;
use App\Models\Traces\Trace;
use App\Modules\Traces\TraceTypeEnum;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class TraceFactory extends Factory
{
    protected $model = Trace::class;

    public function definition(): array
    {
        return [
            'serviceId'     => Service::factory(),
            'traceId'       => $this->faker->uuid(),
            'parentTraceId' => null,
            'type'          => $this->faker->randomElement(TraceTypeEnum::cases()),
            'tags'          => [],
            'data'          => [],
            'loggedAt'      => Carbon::now(),
            'createdAt'     => Carbon::now(),
        ];
    }
}
