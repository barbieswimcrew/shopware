<?php

namespace MollieShopware\Components\Order;

use ArrayObject;
use Enlight_Components_Session_Namespace;

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
     * @param array $basket
     * @param array $sUserData
     * @param array $paymentMethod
     */
    public function prepareOrderSession(array $basket, array $sUserData, array $paymentMethod)
    {
        # the main order variables is the basket, yes
        $sOrderVariables = $basket;

        # ...however inside our order variables
        # there are sub array that are also the basket...aehm..yes... :)
        $sOrderVariables['sBasketView'] = $basket;
        $sOrderVariables['sBasket'] = $basket;

        # make sure our user the data is being
        # correctly added from our previously
        # created guest user
        $sOrderVariables['sUserData'] = $sUserData;

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
