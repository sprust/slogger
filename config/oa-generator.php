<?php

use Ifksco\OpenApiGenerator\Converters\Request\Rules\OaRuleAsStringConverter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\ProhibitedIf;
use Illuminate\Validation\Rules\RequiredIf;
use App\Http\Resources\AbstractApiResource;

return [
    'routes'               => [
        // name => ['routes-prefix']
        'admin-api' => [
            'admin-api',
        ],
    ],
    'security_middlewares' => [
        \App\Modules\Auth\Http\Middlewares\AuthMiddleware::class,
    ],
    'disks'                => [
        'public'  => 'api-json-schemes-public',
        'private' => 'api-json-schemes',
    ],
    'classes'              => [
        'request_parent_class'   => FormRequest::class,
        'resources_parent_class' => AbstractApiResource::class,
    ],
    'custom'               => [
        'responses' => [
            // list of custom resources
        ],
        'requests'  => [
            'converters'                        => [
                // list of custom converters
            ],
            'excluded_rules'                    => [
                RequiredIf::class,
                ProhibitedIf::class,
            ],
            /** @see OaRuleAsStringConverter */
            'excluded_for_fill_rule_as_strings' => [],
        ],
    ],
];
