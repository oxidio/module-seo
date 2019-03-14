<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo;

use fn;
use JsonSerializable;
use OxidEsales\Eshop\{
    Application\Model\Article,
    Application\Model\Category,
    Application\Model\Manufacturer,
    Application\Model\BasketItem,
    Application\Model\OrderArticle,
    Core\Base
};

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
        if ($item instanceof OrderArticle) {
            $art = $item->getArticle();
            $data['quantity'] = $item->getFieldData(OrderArticle\AMOUNT);
            $data['price']    = $item->getPrice()->getPrice();
        } else if ($item instanceof BasketItem) {
            $art = $item->getArticle();
            $data['quantity'] = $item->getAmount();
            $data['price']    = $item->getUnitPrice()->getPrice();
        } else {
            $art = $item;
        }

        $art instanceof Article || fn\fail(__METHOD__);

        $product = new static;
        $product->key        = $art->getId();
        $product->properties = $data + [
            'name'     => $art->getFieldData(Article\TITLE),
            'id'       => $art->getFieldData(Article\ARTNUM),
            'price'    => $art->getPrice()->getPrice(),
            'brand'    => $art->getManufacturer() ? $art->getManufacturer()->getFieldData(Manufacturer\TITLE) : null,
            'category' => $art->getCategory() ? $art->getCategory()->getFieldData(Category\TITLE) : null,
            'variant'  => $art->getFieldData(Article\VARSELECT),
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
