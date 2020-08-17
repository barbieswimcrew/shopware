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
    private $country;

    /**
     * @var string
     */
    private $currency;

    /**
     * @var ApplePayLineItem[]
     */
    private $items;


    /**
     * @param $country
     * @param $currency
     */
    public function __construct($country, $currency)
    {
        $this->country = $country;
        $this->currency = $currency;

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

        return $amount;
    }

    /**
     * @return string
     */
    public function getCountry(): string
    {
        return $this->country;
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
     * @return array
     */
    public function toArray()
    {
        $data = array(
            # apple pay required
            # -------------------------------
            'label' => $this->label,
            'amount' => $this->prepareFloat($this->getAmount()),
            'country' => $this->country,
            'currency' => $this->currency,
            # additional
            # -------------------------------
            'items' => array(),
        );

        /** @var ApplePayLineItem $item */
        foreach ($this->items as $item) {

            $data['items'][] = array(
                # apple pay required
                # -------------------------------
                'label' => $item->getQuantity() . "x " . $item->getName(),
                'type' => 'final',
                'amount' => $this->prepareFloat($item->getPrice()),
                # additional
                # -------------------------------
                'number' => $item->getNumber(),
                'quantity' => $item->getQuantity(),
            );
        }

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
