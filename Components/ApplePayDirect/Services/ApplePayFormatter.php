<?php

namespace MollieShopware\Components\ApplePayDirect\Services;


use MollieShopware\Components\ApplePayDirect\Models\Cart\ApplePayCart;
use MollieShopware\Components\ApplePayDirect\Models\Cart\ApplePayLineItem;
use Shopware\Models\Payment\Payment;

class ApplePayFormatter
{

    /**
     * @param array $method
     * @param $shippingCosts
     * @return array
     */
    public function formatApplePayShippingMethod(array $method, $shippingCosts)
    {
        return array(
            'identifier' => $method['id'],
            'label' => $method['name'],
            'detail' => $method['description'],
            'amount' => $shippingCosts,
        );
    }

    /**
     * @param ApplePayCart $cart
     * @return array
     */
    public function formatCart(ApplePayCart $cart)
    {
        # -----------------------------------------------------
        # CART INIT-DATA
        # -----------------------------------------------------
        $data = array(
            'label' => $cart->getLabel(),
            'amount' => $this->prepareFloat($cart->getAmount()),
            'items' => array(),
        );
        
        # -----------------------------------------------------
        # ADD SUBTOTAL
        # -----------------------------------------------------
        $data['items'][] = array(
            'label' => 'SUBTOTAL',       # todo translation
            'type' => 'final',
            'amount' => $this->prepareFloat($cart->getProductAmount()),
        );

        # -----------------------------------------------------
        # ADD SHIPPING DATA
        # -----------------------------------------------------
        if ($cart->getShipping() instanceof ApplePayLineItem) {
            $data['items'][] = array(
                'label' => $cart->getShipping()->getName(),
                'type' => 'final',
                'amount' => $this->prepareFloat($cart->getShipping()->getPrice()),
            );
        }

        # -----------------------------------------------------
        # ADD TAXES DATA
        # -----------------------------------------------------
        if ($cart->getTaxes() instanceof ApplePayLineItem) {
            $data['items'][] = array(
                'label' => $cart->getTaxes()->getName(),
                'type' => 'final',
                'amount' => $this->prepareFloat($cart->getTaxes()->getPrice()),
            );
        }


        # -----------------------------------------------------
        # TOTAL DATA
        # -----------------------------------------------------
        $data['total'] = array(
            'label' => $cart->getLabel(),
            'amount' => $this->prepareFloat($cart->getAmount()),
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
