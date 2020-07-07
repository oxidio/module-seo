<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo;

use Php;
use Generator;
use IteratorAggregate;
use OxidEsales\Eshop\{
    Application\Controller\BasketController,
    Application\Controller\FrontendController,
    Application\Controller\OrderController,
    Application\Controller\PaymentController,
    Application\Controller\ThankYouController,
    Application\Controller\UserController,
    Application\Model\ArticleList,
    Application\Model\Shop,
    Application\Model\DeliverySet,
    Application\Model\Payment,
    Core\Model\BaseModel,
    Core\Price
};
use Oxidio\Enum\Tables as T;

class DataLayer implements IteratorAggregate
{
    /**
     * @var FrontendController
     */
    private $ctrl;

    /**
     * @var ArticleList[]
     */
    private $lists;

    public function __construct(FrontendController $ctrl, ArticleList ...$lists)
    {
        $this->ctrl  = $ctrl;
        $this->lists = $lists;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): Generator
    {
        if ($impressions = Php::arr($this->impressions())) {
            /** @link https://developers.google.com/tag-manager/enhanced-ecommerce#product-impressions */
            yield self::push(null, [
                'currencyCode' => $this->getCurrencyCode(),
                'impressions'  => $impressions,
            ]);
        }
        if ($product = $this->ctrl->getViewProduct()) {
            /**  @link https://developers.google.com/tag-manager/enhanced-ecommerce#details */
            yield self::push(null, ['detail' => [
                'actionField' => ['list' => $this->getListName()],
                'products'    => [Product::create($product)],
            ]]);
        }

        foreach ($this->cartActions() as $action => $products) {
            yield self::push($action === 'add' ? 'addToCart' : 'removeFromCart' , [
                'currencyCode' => $this->getCurrencyCode(),
                $action        => ['products' => Php::values($products)],
            ]);
        }

        yield from $this->checkoutActions();
        yield from $this->purchaseActions();
    }

    /**
     * @link https://developers.google.com/tag-manager/enhanced-ecommerce#purchases
     */
    private function purchaseActions(): Generator
    {
        if (!$this->ctrl instanceof ThankYouController) {
            return;
        }

        $order = $this->ctrl->getOrder();
        $tax   = array_sum($order->getProductVats(false));
        foreach (['Delivery', 'Payment', 'Wrapping', 'GiftCard'] as $priceType) {
            $price = $order->{"getOrder{$priceType}Price"}();
            if ($price instanceof Price) {
                $tax += $price->getVatValue();
            }
        }

        yield self::push(null, [
            'purchase' => [
                'actionField' => [
                    'id'          => $order->getFieldData(T\ORDER::ORDERNR),
                    'affiliation' => self::field(Shop::class, $order->getShopId(), T\SHOPS::NAME),
                    'revenue'     => $order->getFieldData(T\ORDER::TOTALORDERSUM),
                    'tax'         => $tax,
                    'shipping'    => $order->getFieldData(T\ORDER::DELCOST),
                    'coupon'      => implode(', ', $order->getVoucherNrList()),
                ],
                'products' => Php::values(Product::map($order->getOrderArticles())),
            ]
        ]);
    }

    /**
     * @link https://developers.google.com/tag-manager/enhanced-ecommerce#checkout
     */
    private function checkoutActions(): Generator
    {
        $actionField = null;
        if ($this->ctrl instanceof BasketController) {
            // 1. review cart (basket)
            $actionField = ['step' => 1];
        } else if ($this->ctrl instanceof UserController) {
            if (!$this->ctrl->getUser()) {
                // 2. login
                $option = [
                    1 => 'without registration',
                    2 => 'login', // should not occur
                    3 => 'open account'
                ][$this->ctrl->getLoginOption()] ?? '';
                $actionField = ['step' => 2, 'option' => $option];
            } else {
                // 3. shipping
                $actionField = ['step' => 3];
            }
        } else if ($this->ctrl instanceof PaymentController) {
            yield self::push('checkoutOption', [
                'checkout_option' => ['step' => 3, 'option' => $this->getShippingMethod()]
            ]);
            $option = self::field(Payment::class, $this->ctrl->getCheckedPaymentId(), T\PAYMENTS::DESC);
            // 4. payment (billing)
            $actionField = ['step' => 4, 'option' => $option];
        } else if ($this->ctrl instanceof OrderController) {
            // 5. order (review transaction)
            /** @var Payment $payment */
            $payment = $this->ctrl->getPayment();
            $option  = $payment ? $payment->getFieldData(T\PAYMENTS::DESC) : '';
            yield self::push('checkoutOption', [
                'checkout_option' => ['step' => 4, 'option' => $option]
            ]);
            $actionField = ['step' => 5];
        }

        $actionField && yield self::push('checkout', [
            'actionField' => $actionField,
            'products'    => Php::values($this->cartProducts()),
        ]);
    }

    private static function push(...$args): array
    {
        return Php::traverse(self::event(...$args));
    }

    private static function event(string $event = null, iterable $ecommerce = null): Generator
    {
        $ecommerce && $event && yield 'event' => $event;
        yield 'ecommerce' => Php::traverse($ecommerce ?: [], static function ($data) {
            $data = is_iterable($data) ? Php::traverse($data) : $data;
            return $data ?: null;
        });
    }

    private function impressions(): Generator
    {
        $position = 1;
        foreach ($this->lists as $listName => $articles) {
            foreach ($articles as $article) {
                yield Product::create($article, ['list' => $listName, 'position' => $position++]);
            }
        }
    }

    /**
     * @return Product[]
     */
    private function cartActions(): array
    {
        return Php::traverse($this->cartChanges(), static function ($product) {
            $group = $product->quantity > 0 ? 'add' : 'remove';
            $product->quantity = abs($product->quantity);
            return Php::mapGroup($group);
        });
    }

    /**
     * @return Product[]
     */
    private function cartChanges(): array
    {
        static $changes = null;
        if ($changes !== null) {
            return $changes;
        }
        $changes = ($basket = $this->getBasket()) ? $basket->getChanges() : [];
        return $changes;
    }

    /**
     * @return Product[]
     */
    private function cartProducts(): array
    {
        return Product::map($this->getBasket() ? $this->getBasket()->getContents() : []);
    }

    private function getBasket(): ?Model\SeoBasket
    {
        $session = $this->ctrl->getSession();
        $conf    = $this->ctrl->getConfig();
        $prefix  = $conf->getConfigParam('blMallSharedBasket') ? '' : $conf->getShopId() . '_';
        return $session->hasVariable("{$prefix}basket") ? $session->getBasket() : null;
    }

    private function getShippingMethod(): ?string
    {
        if ($basket = $this->getBasket()) {
            return self::field(DeliverySet::class, $basket->getShippingId(), T\DELIVERYSET::TITLE);
        }
        return null;
    }

    private function getListName(): string
    {
        return $this->ctrl->getViewConfig()->getTopActiveClassName();
    }

    public function getCurrencyCode(): ?string
    {
        return ($currency = $this->ctrl->getActCurrency()) ? $currency->name : null;
    }

    private static function field(string $class, string $id, string $field)
    {
        $object = oxNew($class);
        if ($object instanceof BaseModel && $object->load($id)) {
            return $object->getFieldData($field);
        }
        return null;
    }
}
