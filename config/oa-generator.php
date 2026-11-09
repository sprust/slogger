<?php

use App\Modules\Common\Infrastructure\Http\Resources\AbstractApiResource;
use Ifksco\OpenApiGenerator\Sources\Illuminate\Converters\Request\Rules\OaRuleAsStringConverter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\ProhibitedIf;
use Illuminate\Validation\Rules\RequiredIf;
use App\Modules\Common\Infrastructure\Http\Resources\AbstractStreamedApiResource;

return [
    'sources' => [
        App\Services\OpenApiGenerator\Sources\Illuminate\AdminApiRoutesParser::class,
    ],
    'security_middlewares' => [
        \App\Modules\Auth\Infrastructure\Http\Middlewares\AuthMiddleware::class,
    ],
    'disks'                => [
        [
            'alias'     => 'public',
            'name'      => 'api-json-schemes-public',
            'is_public' => true,
        ],
        [
            'alias'     => 'private',
            'name'      => 'api-json-schemes',
            'is_public' => false,
        ],
    ],
    'classes'              => [
        'request_parent_class'     => FormRequest::class,
        'resources_parent_classes' => [
            AbstractApiResource::class,
            AbstractStreamedApiResource::class,
        ],
    ],
    'custom'               => [
        'path_extensions' => [],
        'responses'       => [
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
            'excluded_for_fill_rule_as_strings' => [
                'json',
                'bail',
            ],
        ],
    ],
];
