<?php

namespace MollieShopware\Components\BasketSnapshot;

class BasketSnapshot
{

    /**
     * @var \Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     *
     */
    const SESSION_KEY = 'mollie-basket-snapshot';


    /**
     * BasketSnapshot constructor.
     *
     * @param \Enlight_Components_Session_Namespace $session
     */
    public function __construct(\Enlight_Components_Session_Namespace $session)
    {
        $this->session = $session;
    }

    /**
     * @param \sBasket $basket
     */
    public function createSnapshot(\sBasket $basket)
    {
        # todo
    }

    /**
     *
     */
    public function restoreSnapshot(\sBasket $basket)
    {
        # todo
    }

}
