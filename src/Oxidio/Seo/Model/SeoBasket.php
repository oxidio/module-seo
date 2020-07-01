<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo\Model;

use OxidEsales\Eshop\Application\Model;
use Oxidio\Seo;

/**
 * @method Model\BasketItem[] getContents()
 */
class SeoBasket extends SeoBasket_parent
{
    private $products = [];

    public function load()
    {
        parent::load();
        $this->products = null;
    }

    public function afterUpdate()
    {
        parent::afterUpdate();
        if ($this->products === null) {
            $this->products = Seo\Product::map($this->getContents());
        }
    }

    /**
     * @return Seo\Product[]
     */
    public function getChanges(): iterable
    {
        $old            = $this->products ?: [];
        $this->products = $new = Seo\Product::map($this->getContents());

        $changes = [];
        if (json_encode($new) === json_encode($old)) {
            return $changes;
        }
        foreach ($old as $key => $product) {
            if ($change = ($new[$key]->quantity ?? 0) - $product->quantity) {
                $product->quantity = $change;
                $changes[$key]     = $product;
            }
            unset($new[$key]);
        }
        foreach ($new as $key => $product) {
            $changes[$key] = $product;
        }
        return $changes;
    }
}

0 && class_alias(Model\Basket::class, SeoBasket_parent::class);
