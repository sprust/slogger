<?php

namespace App\Modules\Trace\Infrastructure\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TraceAdminStoreIndexRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page'         => [
                'required',
                'int',
                'min:1',
            ],
            'version'      => [
                'required',
                'int',
                'min:1',
            ],
            'search_query' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'auto'         => [
                'required',
                'bool',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $inputAuto = $this->input('auto');

        $auto = $inputAuto;

        if (is_integer($auto)) {
            if ($auto === 1) {
                $auto = true;
            } elseif ($auto === 0) {
                $auto = false;
            }
        } elseif (is_string($auto)) {
            $lowerStringAuto = strtolower($auto);

            if ($lowerStringAuto === 'true') {
                $auto = true;
            } elseif ($lowerStringAuto === 'false') {
                $auto = false;
            }
        }

        $this->merge([
            'auto' => $auto,
        ]);
    }
}
