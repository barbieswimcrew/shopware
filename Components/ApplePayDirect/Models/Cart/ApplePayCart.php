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
     * @var string
     */
    private $countryISO;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var ApplePayLineItem[]
     */
    private $items;

    /**
     * @var ApplePayLineItem
     */
    private $shipping;


    /**
     * @param $country
     * @param $currencyISO
     */
    public function __construct($country, $currencyISO)
    {
        $this->countryISO = $country;
        $this->currency = $currencyISO;

        $this->items = array();
    }

    /**
     * @return string
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel(string $label): void
    {
        $this->label = $label;
    }

    /**
     * @return float
     */
    public function getAmount(): float
    {
        $amount = 0;

        /** @var ApplePayLineItem $item */
        foreach ($this->items as $item) {
            $amount += ($item->getQuantity() * $item->getPrice());
        }

        if ($this->shipping instanceof ApplePayLineItem) {
            $amount += $this->shipping->getPrice();
        }

        return $amount;
    }

    /**
     * @return string
     */
    public function getCountryISO(): string
    {
        return $this->countryISO;
    }

    /**
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->currency;
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
     * @return array
     */
    public function toArray()
    {
        # -----------------------------------------------------
        # CART INIT-DATA
        # -----------------------------------------------------
        $data = array(
            'label' => $this->label,
            'amount' => $this->prepareFloat($this->getAmount()),
            'country' => $this->countryISO,
            'currency' => $this->currency,
            'items' => array(),
        );


        # -----------------------------------------------------
        # ADD SUBTOTAL
        # -----------------------------------------------------
        $data['items'][] = array(
            'label' => 'SUBTOTAL',
            'type' => 'final',
            'amount' => $this->prepareFloat($this->getAmount()),
        );

        # -----------------------------------------------------
        # ADD SHIPPING DATA
        # -----------------------------------------------------
        if ($this->shipping instanceof ApplePayLineItem) {
            $data['items'][] = array(
                'label' => $this->shipping->getName(),
                'type' => 'final',
                'amount' => $this->prepareFloat($this->shipping->getPrice()),
            );
        }

        # -----------------------------------------------------
        # TOTAL DATA
        # -----------------------------------------------------
        $data['total'] = array(
            'label' => $this->label,
            'amount' => $this->prepareFloat($this->getAmount()),
            'type' => 'final',
        );

        return $data;
    }

    /**
     * Attention! When json_encode is being used it will
     * automatically display digits like this 23.9999998 instead of 23.99.
     * This is done inside json_encode! So we need to prepare
     * the value by rounding the number up to the number
     * of decimals we find here!
     *
     * @param $value
     * @return float
     */
    private function prepareFloat($value): float
    {
        $countDecimals = strlen(substr(strrchr($value, "."), 1));

        return round($value, $countDecimals);
    }

}
