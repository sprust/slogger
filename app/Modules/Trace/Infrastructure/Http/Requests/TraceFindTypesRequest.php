<?php

namespace App\Modules\Trace\Infrastructure\Http\Requests;

use App\Modules\Trace\Infrastructure\Http\Services\RequestFilterRules;
use Illuminate\Foundation\Http\FormRequest;

class TraceFindTypesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            ...RequestFilterRules::services(),
            ...RequestFilterRules::text(),
            ...RequestFilterRules::loggedFromTo(),
            ...RequestFilterRules::durationFromTo(),
            ...RequestFilterRules::memoryFromTo(),
            ...RequestFilterRules::cpuFromTo(),
            ...RequestFilterRules::data(),
            ...RequestFilterRules::hasProfiling(),
        ];
    }
}
