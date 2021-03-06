<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo\Cli;

use OxidEsales\Eshop\Application\Model\Article;
use OxidEsales\Eshop\Application\Model\Category;
use Oxidio\Core\Database;
use Php\Php;
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
     * @var array
     */
    private $scopes;

    public function __construct(array $scopes = [])
    {
        $this->scopes = $scopes;
    }

    /**
     * Create a site map (@see https://www.sitemaps.org/de/protocol.html)
     *
     * @param ?float $priority (@see https://www.sitemaps.org/de/protocol.html#prioritydef)
     * @param string $frequency (@see https://www.sitemaps.org/de/protocol.html#changefreqdef)
     * @param string[] $scope articles|variants|categories
     *
     * @return iterable
     */
    public function __invoke(
        float $priority = null,
        string $frequency = self::FREQUENCY_DAILY,
        string ...$scope
    ): iterable {
        yield '<?xml version="1.0" encoding="UTF-8"?>';
        yield '<urlset 
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9"
>';
        foreach ($scope as $method) {
            foreach (Php::arr($this->scopes[$method] ?? $this->$method()) as $entry) {
                $entry += [
                    'loc' => $entry[0] ?? null,
                    'lastmod' => substr($entry[1] ?? '', 0, 10) ?: null,
                    'priority' => $priority,
                    'frequency' => $frequency,
                ];
                yield '    <url>';
                yield "        <loc>{$entry['loc']}</loc>";
                $entry['priority'] && yield "        <priority>{$entry['priority']}</priority>";
                $entry['frequency'] && yield "        <changefreq>{$entry['frequency']}</changefreq>";
                $entry['lastmod'] && yield "        <lastmod>{$entry['lastmod']}</lastmod>";
                yield '    </url>';
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
