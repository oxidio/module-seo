<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo\Cli;

use PHPUnit\Framework\TestCase;

class SiteMapTest extends TestCase
{
    public function testIntegration(): void
    {
        $obj = new SiteMap();
        $this::assertNotEmpty(iterator_to_array($obj(null, $obj::FREQUENCY_DAILY, 'articles', 'variants', 'categories')));
    }
}
