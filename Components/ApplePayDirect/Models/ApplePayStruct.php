<?php

namespace MollieShopware\Components\ApplePayDirect\Models;


/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class ApplePayStruct
{

    /**
     * @var bool
     */
    private $active;

    /**
     * @var string
     */
    private $mode;

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
     * @param $active
     * @param $country
     * @param $currency
     */
    public function __construct($active, $country, $currency)
    {
        $this->active = $active;
        $this->country = $country;
        $this->currency = $currency;

        $this->items = array();
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @return string
     */
    public function getMode(): string
    {
        return $this->mode;
    }

    /**
     * @param string $mode
     */
    public function setMode(string $mode): void
    {
        $this->mode = $mode;
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
            'active' => $this->active,
            'mode' => $this->mode,
            'label' => $this->label,
            'amount' => $this->getAmount(),
            'country' => $this->country,
            'currency' => $this->currency,
            'items' => array(),
        );

        /** @var ApplePayLineItem $item */
        foreach ($this->items as $item) {

            $data['items'][] = array(
                'number' => $item->getNumber(),
                'name' => $item->getName(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice(),
            );
        }

        return $data;
    }

}
