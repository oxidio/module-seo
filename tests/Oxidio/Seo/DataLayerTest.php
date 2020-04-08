<?php declare(strict_types=1);
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo;

use OxidEsales\Eshop\Application\Controller\FrontendController;
use OxidEsales\Eshop\Application\Model;
use PHPUnit\Framework\TestCase;

class DataLayerTest extends TestCase
{
    public function testIntegration(): void
    {
        $layer = new DataLayer(new FrontendController(), $list = new Model\ArticleList());
        $list->assign([$art = new Model\Article()]);
        $art->assign(array_fill_keys($art->getFieldNames(), null));
        $this::assertNotEmpty(iterator_to_array($layer));
    }
}
