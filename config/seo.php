<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use DI;
use fn;
use OxidEsales\Eshop\Application\Model\Basket;
use Oxidio\Seo;
use OxidEsales\Eshop\Core\Theme;

return [
    SETTINGS => [
        'Enhanced Ecommerce (UA)' => [
            Seo\GA_ACTIVE  => [
                SETTINGS\VALUE => true,
                SETTINGS\LABEL => 'Activate',
                SETTINGS\HELP  => 'Activate google (U)niversal (A)nalytics (E)nhanced (E)commerce',
            ],
            Seo\GA_ID => [
                SETTINGS\VALUE => (string) getenv('GA_ACCOUNT_ID'),
                SETTINGS\LABEL => 'Id',
                SETTINGS\HELP  => 'GTM container ID (GTM-*) or GA property ID (UA-*)',
            ],
        ]
    ],
    BLOCKS   => [
        Theme\LAYOUT_BASE => [
            Theme\LAYOUT_BASE\BLOCK_HEAD_META_ROBOTS => append(new Seo\Snippet)
        ]
    ],
    EXTEND => [
        Basket::class => Seo\Model\SeoBasket::class
    ],
    CLI => DI\decorate(static function(fn\Cli $cli) {
        $cli->command('sitemap', Seo\Cli\SiteMap::class, ['scope']);
        return $cli;
    }),

    Seo\Cli\SiteMap::class => DI\create(),
];
