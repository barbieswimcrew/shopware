<?php

namespace MollieShopware\Components\ApplePayDirect;


/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class ApplePayViewData
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
     * @var array
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

        /** @var array $item */
        foreach ($this->items as $item) {
            $qty = $item['quantity'];
            $price = $item['price'];

            $amount += ($qty * $price);
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
        $this->items[] = array(
            'number' => $$number,
            'name' => $name,
            'quantity' => $quantity,
            'price' => $price,
        );
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return array(
            'active' => $this->active,
            'mode' => $this->mode,
            'label' => $this->label,
            'amount' => $this->getAmount(),
            'country' => $this->country,
            'currency' => $this->currency,
            'items' => $this->items,
        );
    }

}
