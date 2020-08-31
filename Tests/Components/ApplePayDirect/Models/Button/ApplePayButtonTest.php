<?php

namespace MollieShopware\Tests\Components\ApplePayDirect\Models\Cart;

use MollieShopware\Components\ApplePayDirect\Models\Button\ApplePayButton;
use MollieShopware\Components\ApplePayDirect\Models\Cart\ApplePayCart;
use PHPUnit\Framework\TestCase;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class ApplePayButtonTest extends TestCase
{

    /**
     * This test verifies that item mode is OFF
     * for the default button configuration
     */
    public function testItemModeOff()
    {
        $button = new ApplePayButton(true, 'NL', 'EUR');

        $this->assertEquals(false, $button->isItemMode());
    }

    /**
     * This test verifies that item mode is ON
     * if a certain product is been assigned.
     */
    public function testItemModeOn()
    {
        $button = new ApplePayButton(true, 'NL', 'EUR');
        $button->setItemMode('ABC');

        $this->assertEquals(true, $button->isItemMode());
    }

    /**
     * This test verifies that our array format of the
     * button is correct for the storefront.
     * It also needs to contain the product number
     * if we have item mode ON.
     */
    public function testFormatButton()
    {
        $button = new ApplePayButton(true, 'NL', 'EUR');
        $button->setItemMode('ABC');

        $expected = array(
            'active' => true,
            'country' => 'NL',
            'currency' => 'EUR',
            'itemMode' => true,
            'addNumber' => 'ABC',
        );

        $this->assertEquals($expected, $button->toArray());
    }
    
}
