<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo\Cli;

use Generator;
use OxidEsales\Eshop\{
    Application\Model\Article,
    Application\Model\Category
};
use Oxidio\Core\Database;
use Oxidio\Enum\Tables as T;

class SiteMap
{
    public const FREQUENCY_ALWAYS  = 'always';
    public const FREQUENCY_HOURLY  = 'hourly';
    public const FREQUENCY_DAILY   = 'daily';
    public const FREQUENCY_WEEKLY  = 'weekly';
    public const FREQUENCY_MONTHLY = 'monthly';
    public const FREQUENCY_YEARLY  = 'yearly';
    public const FREQUENCY_NEVER   = 'never';

    /**
     * Create a site map (@see https://www.sitemaps.org/de/protocol.html)
     *
     * @param string[] $scope articles|variants|categories
     * @param float $priority (@see https://www.sitemaps.org/de/protocol.html#prioritydef)
     * @param string $frequency (@see https://www.sitemaps.org/de/protocol.html#changefreqdef)
     *
     * @return Generator
     */
    public function __invoke(
        array $scope,
        float $priority = 0.5,
        string $frequency = self::FREQUENCY_DAILY
    ): Generator {
        yield '<?xml version="1.0" encoding="UTF-8"?>';
        yield '<urlset 
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9"
>';
        foreach ($scope as $method) {
            foreach ($this->$method() as [$loc, $lastMod]) {
                yield from [
                    '    <url>',
                    "        <loc>{$loc}</loc>",
                    "        <priority>{$priority}</priority>",
                    "        <lastmod>{$lastMod}</lastmod>",
                    "        <changefreq>{$frequency}</changefreq>",
                    '    </url>',
                ];
            }
        }

        yield '</urlset>';
    }

    protected function articles(): iterable
    {
        return Database::get()->query(...[static function (Article $model, $timeStamp) {
            return [$model->getMainLink(), $timeStamp];
        }])->where([T\ARTICLES::ACTIVE => 1, T\ARTICLES::PARENTID => '']);
    }

    protected function variants(): iterable
    {
        return Database::get()->query(...[static function (Article $model, $timeStamp) {
            return [$model->getMainLink(), $timeStamp];
        }])->where([T\ARTICLES::ACTIVE => 1, T\ARTICLES::PARENTID => ['<>', '']]);
    }

    protected function categories(): iterable
    {
        return Database::get()->query(...[static function (Category $model, $timeStamp) {
            return [$model->getLink(), $timeStamp];
        }])->where([T\CATEGORIES::ACTIVE => 1, T\CATEGORIES::HIDDEN => 0]);
    }
}
