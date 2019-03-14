<?php
/**
 * Copyright (C) oxidio. See LICENSE file for license details.
 */

namespace Oxidio\Seo;

use fn;
use Generator;
use IteratorAggregate;
use OxidEsales\Eshop\{Application\Controller,
    Application\Model,
    Core\Controller\BaseController,
    Core\Model\BaseModel,
    Core\Price
};
use SplStack;

class DataLayer implements IteratorAggregate
{
    /**
     * @var BaseController
     */
    private $ctrl;
    /**
     * @var iterable
     */
    private $templateVars;

    public function __construct(BaseController $ctrl, iterable $templateVars)
    {
        $this->ctrl         = $ctrl;
        $this->templateVars = $templateVars;
    }

    /**
     * @inheritdoc
     */
    public function getIterator(): Generator
    {
        if ($impressions = fn\traverse($this->impressions())) {
            /** @link https://developers.google.com/tag-manager/enhanced-ecommerce#product-impressions */
            yield self::push(null, [
                'currencyCode' => $this->getCurrencyCode(),
                'impressions'  => $impressions,
            ]);
        }
        if ($product = $this->getProduct()) {
            /**  @link https://developers.google.com/tag-manager/enhanced-ecommerce#details */
            yield self::push(null, ['detail' => [
                'actionField' => ['list' => $this->getListName()],
                'products'    => [Product::create($product)],
            ]]);
        }

        foreach ($this->cartActions() as $action => $products) {
            yield self::push($action === 'add' ? 'addToCart' : 'removeFromCart' , [
                'currencyCode' => $this->getCurrencyCode(),
                $action        => ['products' => fn\values($products)],
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
        if (!$this->ctrl instanceof Controller\ThankYouController) {
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

        yield self::push('purchase', [
            'actionField' => [
                'id'          => $order->getFieldData(Model\Order\ORDERNR),
                'affiliation' => self::field(Model\Shop::class, $order->getShopId(), Model\Shop\NAME),
                'revenue'     => $order->getFieldData(Model\Order\TOTALORDERSUM),
                'tax'         => $tax,
                'shipping'    => $order->getFieldData(Model\Order\DELCOST),
                'coupon'      => implode(', ', $order->getVoucherNrList()),
            ],
            'products'    => fn\values(Product::map($order->getOrderArticles())),
        ]);
    }

    /**
     * @link https://developers.google.com/tag-manager/enhanced-ecommerce#checkout
     */
    private function checkoutActions(): Generator
    {
        $actionField = null;
        if ($this->ctrl instanceof Controller\BasketController) {
            // 1. review cart (basket)
            $actionField = ['step' => 1];
        } else if ($this->ctrl instanceof Controller\UserController) {
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
        } else if ($this->ctrl instanceof Controller\PaymentController) {
            yield self::push('checkoutOption', [
                'checkout_option' => ['step' => 3, 'option' => $this->getShippingMethod()]
            ]);
            $option = self::field(Model\Payment::class, $this->ctrl->getCheckedPaymentId(), Model\Payment\DESC);
            // 4. payment (billing)
            $actionField = ['step' => 4, 'option' => $option];
        } else if ($this->ctrl instanceof Controller\OrderController) {
            // 5. order (review transaction)
            /** @var Model\Payment $payment */
            $payment = $this->ctrl->getPayment();
            $option  = $payment ? $payment->getFieldData(Model\Payment\DESC) : '';
            yield self::push('checkoutOption', [
                'checkout_option' => ['step' => 4, 'option' => $option]
            ]);
            $actionField = ['step' => 5];
        }

        $actionField && yield self::push('checkout', [
            'actionField' => $actionField,
            'products'    => fn\values($this->cartProducts()),
        ]);
    }

    private static function push(...$args): array
    {
        return fn\traverse(self::event(...$args));
    }

    private static function event(string $event = null, iterable $ecommerce = null): Generator
    {
        $ecommerce && $event && yield 'event' => $event;
        yield 'ecommerce' => fn\traverse($ecommerce ?: [], function($data) {
            $data = is_iterable($data) ? fn\traverse($data) : $data;
            return $data ?: null;
        });
    }

    private function impressions(): Generator
    {
        $position = 1;
        foreach ($this->getLists() as $listName => $articles) {
            if (!$articles instanceof Model\ArticleList) {
                continue;
            }
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
        return fn\traverse($this->cartChanges(), function($product) {
            $group = $product->quantity > 0 ? 'add' : 'remove';
            $product->quantity = abs($product->quantity);
            return fn\mapGroup($group);
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

        $session = $this->ctrl->getSession();
        if (!$this->getBasket()) { // logout
            $session->deleteVariable('ga:cart-history');
            return [];
        }

        $changes = [];

        if (!($history = $session->getVariable('ga:cart-history'))) {
            $session->setVariable('ga:cart-history', $history = new SplStack);
            $history->push($this->cartProducts());
        }

        /**
         * @var Product[] $new
         * @var Product[] $old
         */

        if (json_encode($new = $this->cartProducts()) === json_encode($old = $history->top())) {
            return [];
        }
        $history->push($new);

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

    /**
     * @return Product[]
     */
    private function cartProducts(): array
    {
        return Product::map($this->getBasket() ? $this->getBasket()->getContents() : []);
    }

    private function getBasket(): ?Model\Basket
    {
        $session = $this->ctrl->getSession();
        $conf    = $this->ctrl->getConfig();
        $prefix  = $conf->getConfigParam('blMallSharedBasket') ? '' : $conf->getShopId() . '_';
        return $session->hasVariable("{$prefix}basket") ? $session->getBasket() : null;
    }

    private function getShippingMethod(): ?string
    {
        if ($basket = $this->getBasket()) {
            return self::field(Model\DeliverySet::class, $basket->getShippingId(), Model\DeliverySet\TITLE);
        }
        return null;
    }

    private function getProduct(): ?BaseModel
    {
        return $this->ctrl instanceof Controller\FrontendController ? $this->ctrl->getViewProduct() : null;
    }

    private function getListName(): string
    {
        return $this->ctrl->getViewConfig()->getTopActiveClassName();
    }

    private function getLists(): iterable
    {
        $lists = $this->templateVars;
        if ($this->ctrl instanceof Controller\FrontendController) {
            $lists[$this->getListName()] = $this->ctrl->getViewProductList() ?: [];
        }
        return $lists;
    }

    public function getCurrencyCode(): ?string
    {
        $currency = $this->ctrl instanceof Controller\FrontendController ? $this->ctrl->getActCurrency() : null;
        return $currency ? $currency->name : null;
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
