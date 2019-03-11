<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

use Oxidio\{Module};

return [
    'module' => function (Module\Settings $settings) {
        return [
            'id' => 'oxidio/seo',
            'title' => 'oxidio/module-seo',
            'url' => 'https://github.com/oxidio/module-seo',
            'author' => 'oxidio',
            'settings' => fn\traverse($settings),
        ];
    },

    Module\Settings::class => function () {
        return new Module\Settings([
            'SEO' => [],
        ]);
    },
];
