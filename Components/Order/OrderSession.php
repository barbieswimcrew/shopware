<?php

namespace MollieShopware\Components\Order;

use ArrayObject;
use Enlight_Components_Session_Namespace;
use Shopware_Controllers_Frontend_Checkout;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class OrderSession
{

    /**
     *
     * @var Enlight_Components_Session_Namespace
     */
    private $session;


    /**
     * @param Enlight_Components_Session_Namespace $session
     */
    public function __construct(Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    /**
     * @param Shopware_Controllers_Frontend_Checkout $checkoutController
     * @param array $paymentMethod
     */
    public function prepareOrderSession(Shopware_Controllers_Frontend_Checkout $checkoutController, array $paymentMethod)
    {
        $basket = $checkoutController->getBasket(false);

        # the main order variables is the basket, yes
        $sOrderVariables = $basket;

        # ...however inside our order variables
        # there are sub array that are also the basket...aehm..yes... :)
        $sOrderVariables['sBasketView'] = $basket;
        $sOrderVariables['sBasket'] = $basket;

        # make sure our user the data is being
        # correctly added from our previously
        # created guest user
        $sOrderVariables['sUserData'] = $checkoutController->View()->getAssign('sUserData');

        # make sure we always use "apple pay direct"
        # for the order we create
        $sOrderVariables['sUserData'] ['additional']['user']['paymentID'] = $paymentMethod['id'];
        $sOrderVariables['sUserData'] ['additional']['payment'] = $paymentMethod;

        # finish our variables (shopware default)
        $sOrderVariables = new ArrayObject($sOrderVariables, ArrayObject::ARRAY_AS_PROPS);

        # add the prepared order to our session
        $this->session->offsetSet('sOrderVariables', $sOrderVariables);
    }

}
