<?php

use Ifksco\OpenApiGenerator\Sources\Illuminate\Converters\Request\Rules\BaseOaRuleConverter;
use Ifksco\OpenApiGenerator\Sources\Illuminate\Converters\Request\Rules\OaRuleAsStringConverter;
use Ifksco\OpenApiGenerator\Sources\Illuminate\Custom\OaBaseCustomResponseInterface;
use Ifksco\OpenApiGenerator\Sources\Illuminate\Custom\OaPathExtensionsResolverInterface;
use Ifksco\OpenApiGenerator\Sources\SourceInterface;
use Illuminate\Validation\Rules\ProhibitedIf;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Resources\Json\JsonResource;

return [
    /**
     * Classes that implements SourceInterface
     *
     * @see SourceInterface
     */
    'sources'              => [
        //
    ],
    /**
     * Add 'security' value to openapi scheme
     */
    'security_middlewares' => [
        //
    ],
    /**
     * Disks for save schemes
     *
     * {
     *  alias: string - disk alias
     *  name: string - disk name (filesystems.disks)
     *  is_public: bool - is public disk (add servers to openapi scheme)
     * }
     */
    'disks'                => [
        //
    ],
    'classes'              => [
        'request_parent_class'     => FormRequest::class,
        'resources_parent_classes' => [
            JsonResource::class,
        ],
    ],
    'custom'               => [
        /**
         * Classes that implements OaPathExtensionsResolverInterface
         *
         * @see OaPathExtensionsResolverInterface
         */
        'path_extensions' => [
            // list of custom resolvers
        ],
        /**
         * Classes that implements OaBaseCustomResponseInterface
         *
         * @see OaBaseCustomResponseInterface
         */
        'responses' => [
            // list of custom resources
        ],
        'requests'  => [
            /**
             * Classes that implements BaseOaRuleConverter
             *
             * @see BaseOaRuleConverter
             */
            'converters'                        => [
                // list of custom converters
            ],
            'excluded_rules'                    => [
                RequiredIf::class,
                ProhibitedIf::class,
                'mimes',
                'before_or_equal',
                'after_or_equal',
            ],
            /** @see OaRuleAsStringConverter */
            'excluded_for_fill_rule_as_strings' => [],
        ],
    ],
];
