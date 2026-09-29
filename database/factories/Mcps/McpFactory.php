<?php

namespace Database\Factories\Mcps;

use App\Models\Mcps\Mcp;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class McpFactory extends Factory
{
    protected $model = Mcp::class;

    public function definition(): array
    {
        return [
            'name'         => uniqid(),
            'token'        => Str::random(50),
            'enabled'      => true,
            'last_used_at' => null,
            'created_at'   => Carbon::now(),
            'updated_at'   => Carbon::now(),
        ];
    }
}
