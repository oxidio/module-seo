<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use DI;
use fn;
use Oxidio\Command;

return [
    ID       => 'oxidio/seo',
    TITLE    => 'oxidio/module-seo',
    URL      => 'https://github.com/oxidio/module-seo',
    AUTHOR   => 'oxidio',
    SETTINGS => [],

    'cli' => DI\decorate(function(fn\Cli $cli) {
        $cli->command('sitemap', Command\SiteMap::class, ['scope']);
        return $cli;
    }),

    Command\SiteMap::class => DI\create(),
];
