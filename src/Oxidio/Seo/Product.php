<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo;

use php;
use JsonSerializable;
use OxidEsales\Eshop\{
    Application\Model\Article,
    Application\Model\BasketItem,
    Application\Model\OrderArticle,
    Core\Base,
    Core\Database\TABLE\OXCATEGORIES,
    Core\Database\TABLE\OXMANUFACTURERS,
    Core\Database\TABLE\OXARTICLES,
    Core\Database\TABLE\OXORDERARTICLES
};

/**
 * @property int $quantity
 */
class Product implements JsonSerializable
{
    use php\PropertiesTrait;

    private $key;

    public static function create(Base $item, array $data = []): self
    {
        if ($item instanceof OrderArticle) {
            $art = $item->getArticle();
            $data['quantity'] = $item->getFieldData(OXORDERARTICLES\OXAMOUNT);
            $data['price']    = $item->getPrice()->getPrice();
        } else if ($item instanceof BasketItem) {
            $art = $item->getArticle();
            $data['quantity'] = $item->getAmount();
            $data['price']    = $item->getUnitPrice()->getPrice();
        } else {
            $art = $item;
        }

        $art instanceof Article || php\fail(__METHOD__);

        $product = new static;
        $product->key        = $art->getId();
        $product->properties = $data + [
            'name'     => $art->getFieldData(OXARTICLES\OXTITLE),
            'id'       => $art->getFieldData(OXARTICLES\OXARTNUM),
            'price'    => $art->getPrice()->getPrice(),
            'brand'    => $art->getManufacturer() ? $art->getManufacturer()->getFieldData(OXMANUFACTURERS\OXTITLE) : null,
            'category' => $art->getCategory() ? $art->getCategory()->getFieldData(OXCATEGORIES\OXTITLE) : null,
            'variant'  => $art->getFieldData(OXARTICLES\OXVARSELECT),
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
        return php\map($items, static function($item) use($data) {
            return php\mapValue($product = static::create($item, $data))->andKey($product->key);
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
