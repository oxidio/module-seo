<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo;

use php;
use JsonSerializable;
use OxidEsales\EshopCommunity\{
    Application\Model,
    Core\Base
};
use Oxidio\Enum\Tables as T;

/**
 * @property int $quantity
 */
class Product implements JsonSerializable
{
    use php\PropertiesTrait;

    private $key;

    public static function create(Base $item, array $data = []): self
    {
        if ($item instanceof Model\OrderArticle) {
            $art = $item->getArticle();
            $data['quantity'] = $item->getFieldData(T\Orderarticles::AMOUNT);
            $data['price']    = $item->getPrice()->getPrice();
        } else if ($item instanceof Model\BasketItem) {
            $art = $item->getArticle();
            $data['quantity'] = $item->getAmount();
            $data['price']    = $item->getUnitPrice()->getPrice();
        } else {
            $art = $item;
        }

        $art instanceof Model\Article || php\fail(__METHOD__);

        $product = new static;
        $product->key        = $art->getId();
        $product->properties = $data + [
            'name'     => $art->getFieldData(T\Articles::TITLE),
            'id'       => $art->getFieldData(T\Articles::ARTNUM),
            'price'    => $art->getPrice()->getPrice(),
            'brand'    => ($man = $art->getManufacturer(false)) && $man->getId() ? $man->getFieldData(T\Manufacturers::TITLE) : null,
            'category' => ($cat = $art->getCategory()) && $cat->getId() ? $cat->getFieldData(T\Categories::TITLE) : null,
            'variant'  => $art->getFieldData(T\Articles::VARSELECT),
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
