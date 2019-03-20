<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo\Cli;

use fn;
use OxidEsales\Eshop\Application\Model\{Article, Category};
use Oxidio;


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
     * @param fn\Cli\IO $io
     * @param string[] $scope articles|variants|categories
     * @param float $priority (@see https://www.sitemaps.org/de/protocol.html#prioritydef)
     * @param string $frequency (@see https://www.sitemaps.org/de/protocol.html#changefreqdef)
     */
    public function __invoke(
        fn\Cli\IO $io,
        array $scope,
        float $priority = 0.5,
        string $frequency = self::FREQUENCY_DAILY
    ): void {
        $io->writeln([
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset 
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9"
>',
        ]);

        foreach ($scope as $method) {
            $query = $this->$method();
            $io->isVerbose() && $io->note($query);
            foreach ($query as [$loc, $lastMod]) {
                $io->writeln([
                    '    <url>',
                    "        <loc>{$loc}</loc>",
                    "        <priority>{$priority}</priority>",
                    "        <lastmod>{$lastMod}</lastmod>",
                    "        <changefreq>{$frequency}</changefreq>",
                    '    </url>',
                ]);
            }
        }

        $io->writeln('</urlset>');
    }

    protected function articles(): iterable
    {
        return Oxidio\query(function(Article $model, $timeStamp) {
            return [$model->getMainLink(), $timeStamp];
        })->where([Article\ACTIVE => 1, Article\PARENTID => '']);
    }

    protected function variants(): iterable
    {
        return Oxidio\query(function(Article $model, $timeStamp) {
            return [$model->getMainLink(), $timeStamp];
        })->where([Article\ACTIVE => 1, Article\PARENTID => ['<>', '']]);
    }

    protected function categories(): iterable
    {
        return Oxidio\query(function(Category $model, $timeStamp) {
            return [$model->getLink(), $timeStamp];
        })->where([Category\ACTIVE => 1, Category\HIDDEN => 0]);
    }
}
