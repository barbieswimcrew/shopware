<?php

namespace MollieShopware\Tests\Components\ApplePayDirect\Models\Cart;

use MollieShopware\Components\ApplePayDirect\Models\Cart\ApplePayCart;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class ApplePayCartTest extends TestCase
{

    /**
     * This test verifies that the country
     * value is set and used correctly.
     */
    public function testCountry()
    {
        $cart = new ApplePayCart('NL', 'EUR');

        $this->assertEquals('NL', $cart->getCountry());
    }

    /**
     * This test verifies that the currency
     * value is set and used correctly.
     */
    public function testCurrency()
    {
        $cart = new ApplePayCart('NL', 'EUR');

        $this->assertEquals('EUR', $cart->getCurrency());
    }

}
