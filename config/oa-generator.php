<?php

use App\Modules\Common\Http\Resources\AbstractApiResource;
use Ifksco\OpenApiGenerator\Converters\Request\Rules\OaRuleAsStringConverter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\ProhibitedIf;
use Illuminate\Validation\Rules\RequiredIf;

return [
    'routes'               => [
        // name => ['routes-prefix']
        'admin-api' => [
            'admin-api',
        ],
    ],
    'security_middlewares' => [
        \App\Modules\Auth\Framework\Http\Middlewares\AuthMiddleware::class,
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
