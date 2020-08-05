<?php

namespace MollieShopware\Components\Shipping;

use Enlight_Components_Session_Namespace;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class Shipping
{
    /**
     * @var \sAdmin
     */
    private $admin;

    /**
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * @param \sAdmin $admin
     * @param Enlight_Components_Session_Namespace $session
     */
    public function __construct(\sAdmin $admin, Enlight_Components_Session_Namespace $session)
    {
        $this->admin = $admin;
        $this->session = $session;
    }

    /**
     * @param $countryID
     * @param $paymentID
     * @return array
     */
    public function getShippingMethods($countryID, $paymentID)
    {
        return $this->admin->sGetPremiumDispatches($countryID, $paymentID);
    }

    /**
     * @param $country
     * @param $shippingMethodId
     * @return array|int|int[]|mixed
     */
    public function getShippingMethodCosts($country, $shippingMethodId)
    {
        $previousDispatch = $this->getCartShippingMethodID();

        $this->setCartShippingMethodID($shippingMethodId);

        $costs = $this->admin->sGetPremiumShippingcosts($country);

        $this->setCartShippingMethodID($previousDispatch);

        return $costs['value'];
    }

    /**
     * @param $shippingMethodId
     */
    public function setCartShippingMethodID($shippingMethodId)
    {
        $this->session['sDispatch'] = $shippingMethodId;
    }

    /**
     * @return mixed
     */
    public function getCartShippingMethodID()
    {
        return $this->session['sDispatch'];
    }

}
