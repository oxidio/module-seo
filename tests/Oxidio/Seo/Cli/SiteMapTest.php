<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo\Cli;

use PHPUnit\Framework\TestCase;

class SiteMapTest extends TestCase
{
    public function testIntegration(): void
    {
        $this::assertNotEmpty(iterator_to_array((new SiteMap())(['articles', 'variants', 'categories'])));
    }
}
