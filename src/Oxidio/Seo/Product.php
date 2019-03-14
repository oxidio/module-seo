<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo;

use fn;
use JsonSerializable;
use OxidEsales\Eshop\{Application\Model, Core\Base};

/**
 * @property int $quantity
 */
class Product implements JsonSerializable
{
    use fn\Meta\Properties\ReadWriteTrait;

    protected $properties;

    private $key;

    public static function create(Base $item, array $data = []): self
    {
        if ($item instanceof Model\OrderArticle) {
            $art = $item->getArticle();
            $data['quantity'] = $item->getFieldData('oxamount');
            $data['price']    = $item->getPrice()->getPrice();
        } else if ($item instanceof Model\BasketItem) {
            $art = $item->getArticle();
            $data['quantity'] = $item->getAmount();
            $data['price']    = $item->getUnitPrice()->getPrice();
        } else {
            $art = $item;
        }

        $art instanceof Model\Article || fn\fail(__METHOD__);

        $product = new static;
        $product->key        = $art->getId();
        $product->properties = $data + [
            'name'     => $art->getFieldData('oxtitle'),
            'id'       => $art->getFieldData('oxartnum'),
            'price'    => $art->getPrice()->getPrice(),
            'brand'    => $art->getManufacturer() ? $art->getManufacturer()->getFieldData('oxtitle') : null,
            'category' => $art->getCategory() ? $art->getCategory()->getFieldData('oxtitle') : null,
            'variant'  => $art->getFieldData('oxvarselect'),
        ];

        return $product;
    }

    /**
     * @param iterable $items
     * @param array    $data
     *
     * @return static[]
     */
    public static function map(iterable $items, array $data = []): array
    {
        return fn\map($items, function($item) use($data) {
            return fn\mapValue($product = static::create($item, $data))->andKey($product->key);
        })->sort()->traverse;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return $this->properties;
    }
}
