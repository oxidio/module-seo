<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo;

use OxidEsales\Eshop\Application\Model\Article;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testIntegration(): void
    {
        $art = new Article();
        $art->assign(array_fill_keys($art->getFieldNames(), null));
        $this::assertNotEmpty(Product::map([$art]));
    }
}
