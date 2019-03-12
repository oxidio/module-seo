<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use DI;
use fn;
use Oxidio\Seo;

return [
    ID       => 'oxidio/seo',
    TITLE    => 'oxidio/module-seo',
    URL      => 'https://github.com/oxidio/module-seo',
    AUTHOR   => 'oxidio',
    SETTINGS => [],

    'cli' => DI\decorate(function(fn\Cli $cli) {
        $cli->command('sitemap', Seo\SiteMap::class);
        return $cli;
    }),

    Seo\SiteMap::class => DI\create(),
];
