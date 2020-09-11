<?php

namespace MollieShopware\Components\Order;

use ArrayObject;
use Enlight_Components_Session_Namespace;
use Shopware\Bundle\StoreFrontBundle\Gateway\PaymentGatewayInterface;
use Shopware\Bundle\StoreFrontBundle\Struct\ShopContext;
use Shopware\Components\Compatibility\LegacyStructConverter;
use Shopware\Models\Payment\Payment;
use Shopware_Controllers_Frontend_Checkout;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class OrderSession
{

    /**
     * @var LegacyStructConverter
     */
    private $legacyStructConverter;

    /**
     * @var PaymentGatewayInterface
     */
    private $paymentGateway;

    /**
     *
     * @var Enlight_Components_Session_Namespace
     */
    private $session;

    /**
     * @param LegacyStructConverter $legacyStructConverter
     * @param Enlight_Components_Session_Namespace $session
     */
    public function __construct(LegacyStructConverter $legacyStructConverter, Enlight_Components_Session_Namespace $session)
    {
        $this->legacyStructConverter = $legacyStructConverter;
        $this->session = $session;

        # attention, doesnt exist in CLI
        $this->paymentGateway = Shopware()->Container()->get("shopware_storefront.payment_gateway");
    }


    /**
     * @param Shopware_Controllers_Frontend_Checkout $checkoutController
     * @param Payment $paymentMethod
     * @param ShopContext $shopContext
     */
    public function prepareOrderSession(Shopware_Controllers_Frontend_Checkout $checkoutController, Payment $payment, ShopContext $shopContext)
    {
        # convert our shopware payment
        # to a storefront payment method, which allows us to use the
        # legacy struct converter, because we need that ARRAY in the end ;)
        $storefrontPaymentMethods = $this->paymentGateway->getList(array($payment->getId()), $shopContext);
        $paymentMethod = $storefrontPaymentMethods[$payment->getId()];
        $arrayPaymentMethod = $this->legacyStructConverter->convertPaymentStruct($paymentMethod);


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
        $sOrderVariables['sUserData'] ['additional']['user']['paymentID'] = $paymentMethod->getId();
        $sOrderVariables['sUserData'] ['additional']['payment'] = $arrayPaymentMethod;

        # finish our variables (shopware default)
        $sOrderVariables = new ArrayObject($sOrderVariables, ArrayObject::ARRAY_AS_PROPS);

        # add the prepared order to our session
        $this->session->offsetSet('sOrderVariables', $sOrderVariables);
    }

}
