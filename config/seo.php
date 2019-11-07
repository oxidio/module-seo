<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Module;

use Php;
use OxidEsales\Eshop\Application\Model\Basket;
use Oxidio\Seo;
use OxidEsales\Eshop\Core\Theme;

return [
    Module::SETTINGS => [
        'Enhanced Ecommerce (UA)' => [
            Seo\GA_ACTIVE  => [
                Settings::VALUE => true,
                Settings::LABEL => 'Activate',
                Settings::HELP => 'Activate google (U)niversal (A)nalytics (E)nhanced (E)commerce',
            ],
            Seo\GA_ID => [
                Settings::VALUE => (string) getenv('GA_ACCOUNT_ID'),
                Settings::LABEL => 'Id',
                Settings::HELP => 'GTM container ID (GTM-*) or GA property ID (UA-*)',
            ],
        ]
    ],
    Module::BLOCKS => [
        Theme\LAYOUT_BASE => [
            Theme\LAYOUT_BASE\BLOCK_HEAD_META_ROBOTS => Block::append(new Seo\Snippet)
        ]
    ],
    Module::EXTEND => [
        Basket::class => Seo\Model\SeoBasket::class
    ],
    Module::CLI => static function(Php\Cli $cli) {
        $cli->command('sitemap', new Seo\Cli\SiteMap, ['scope']);
        return $cli;
    },
];
