<?php

namespace Database\Factories\Traces;

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
            'service'       => $this->faker->word(),
            'trackId'       => $this->faker->uuid(),
            'parentTrackId' => null,
            'type'          => $this->faker->randomElement(TraceTypeEnum::cases()),
            'tags'          => [],
            'data'          => [],
            'loggedAt'      => Carbon::now(),
            'createdAt'     => Carbon::now(),
        ];
    }
}
