<?php

namespace MollieShopware\Components\ApplePayDirect\Models\Cart;


/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class ApplePayCart
{

    /**
     * @var string
     */
    private $label;

    /**
     * @var ApplePayLineItem[]
     */
    private $items;

    /**
     * @var ApplePayLineItem
     */
    private $shipping;

    /**
     * @var ApplePayLineItem
     */
    private $taxes;

    /**
     */
    public function __construct()
    {
        $this->items = array();

        $this->shipping = null;
        $this->taxes = null;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return ApplePayLineItem
     */
    public function getShipping()
    {
        return $this->shipping;
    }

    /**
     * @return ApplePayLineItem
     */
    public function getTaxes()
    {
        return $this->taxes;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        $amount = $this->getProductAmount();

        if ($this->shipping instanceof ApplePayLineItem) {
            $amount += $this->shipping->getPrice();
        }

        return $amount;
    }

    /**
     * @return float|int
     */
    public function getProductAmount()
    {
        $amount = 0;

        /** @var ApplePayLineItem $item */
        foreach ($this->items as $item) {
            $amount += ($item->getQuantity() * $item->getPrice());
        }

        return $amount;
    }

    /**
     * @return array
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param $number
     * @param $name
     * @param $quantity
     * @param $price
     */
    public function addItem($number, $name, $quantity, $price)
    {
        $this->items[] = new ApplePayLineItem($number, $name, $quantity, $price);
    }

    /**
     * @param $name
     * @param $price
     */
    public function setShipping($name, $price)
    {
        $this->shipping = new ApplePayLineItem("SHIPPING", $name, 1, $price);
    }

    /**
     * @param $name
     * @param $price
     */
    public function setTaxes($name, $price)
    {
        $this->taxes = new ApplePayLineItem("TAXES", $name, 1, $price);
    }

}
