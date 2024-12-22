<?php

declare(strict_types=1);

namespace App\Modules\Trace\Infrastructure\Http\Requests;

use App\Modules\Trace\Infrastructure\Http\Services\RequestFilterRules;
use Illuminate\Foundation\Http\FormRequest;

class TraceFindStatusesRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            ...RequestFilterRules::services(),
            ...RequestFilterRules::text(),
            ...RequestFilterRules::types(),
            ...RequestFilterRules::types(),
            ...RequestFilterRules::tags(),
            ...RequestFilterRules::loggedFromTo(),
            ...RequestFilterRules::durationFromTo(),
            ...RequestFilterRules::memoryFromTo(),
            ...RequestFilterRules::cpuFromTo(),
            ...RequestFilterRules::data(),
            ...RequestFilterRules::hasProfiling(),
        ];
    }
}
