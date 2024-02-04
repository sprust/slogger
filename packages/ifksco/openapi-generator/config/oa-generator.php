<?php

use Ifksco\OpenApiGenerator\Converters\Request\Rules\OaRuleAsStringConverter;
use Illuminate\Validation\Rules\ProhibitedIf;
use Illuminate\Validation\Rules\RequiredIf;

return [
    'routes'               => [
        // name => ['routes-prefix']
        'common' => [
            'api',
        ],
    ],
    'security_middlewares' => [
        'auth:api',
    ],
    'disks'                => [
        'public'  => 'local',
        'private' => 'local',
    ],
    'classes'              => [
        'request_parent_class'   => '',
        'resources_parent_class' => '',
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
