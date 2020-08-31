<?php

namespace MollieShopware\Components\ApplePayDirect\Handler;

use Mollie\Api\MollieApiClient;
use MollieShopware\Components\ApplePayDirect\ApplePayDirectHandlerInterface;
use MollieShopware\Components\ApplePayDirect\Models\Cart\ApplePayCart;
use MollieShopware\Components\Shipping\Shipping;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class ApplePayDirectHandler implements ApplePayDirectHandlerInterface
{

    /**
     * This is the key for the session entry
     * that stores the payment token before
     * finishing a new order
     */
    const KEY_SESSION_PAYMENTTOKEN = 'MOLLIE_APPLEPAY_PAYENTTOKEN';


    /**
     * @var MollieApiClient
     */
    private $clientLive;

    /**
     * @var MollieApiClient
     */
    private $clientTest;

    /**
     * @var bool
     */
    private $isTestModeEnabled;

    /**
     * @var \sAdmin
     */
    private $admin;

    /**
     * @var \sBasket
     */
    private $basket;

    /**
     * @var Shipping $shipping
     */
    private $shipping;

    /**
     * @var \Enlight_Components_Session_Namespace
     */
    private $session;


    /**
     * ApplePayDirectHandler constructor.
     *
     * @param MollieApiClient $clientLive
     * @param MollieApiClient $clientTest
     * @param bool $isTestModeEnabled
     * @param $sAdmin
     * @param $sBasket
     * @param Shipping $cmpShipping
     * @param \Enlight_Components_Session_Namespace $session
     */
    public function __construct(MollieApiClient $clientLive, MollieApiClient $clientTest, bool $isTestModeEnabled, $sAdmin, $sBasket, Shipping $cmpShipping, \Enlight_Components_Session_Namespace $session)
    {
        $this->clientLive = $clientLive;
        $this->clientTest = $clientTest;
        $this->isTestModeEnabled = $isTestModeEnabled;

        $this->admin = $sAdmin;
        $this->basket = $sBasket;
        $this->shipping = $cmpShipping;
    }


    /**
     * @return mixed|ApplePayCart
     * @throws \Enlight_Exception
     */
    public function getApplePayCart()
    {
        $cart = new ApplePayCart();

        $taxes = 0;

        /** @var array $item */
        foreach ($this->basket->sGetBasketData()['content'] as $item) {

            $cart->addItem(
                $item['ordernumber'],
                $item['articlename'],
                (int)$item['quantity'],
                (float)$item['priceNumeric']
            );

            $taxes += (float)str_replace(',', '.', $item['tax']);
        }

        # load our purchase country
        # while we still show the apple pay sheet
        # this is always handled through this variable.
        $country = $this->admin->sGetUserData()['additional']['country'];

        /** @var array $shipping */
        $shipping = $this->admin->sGetPremiumShippingcosts($country);

        if ($shipping['value'] !== null && $shipping['value'] > 0) {

            /** @var array $shipmentMethod */
            $shipmentMethod = $this->shipping->getCartShippingMethod();

            $cart->setShipping($shipmentMethod['name'], (float)$shipping['value']);

            $taxes += ($shipping['brutto'] - $shipping['netto']);
        }

        # also add our taxes value
        # if we have one
        if ($taxes > 0) {
            $cart->setTaxes($taxes);
        }

        return $cart;
    }

    /**
     * @param $domain
     * @param $validationUrl
     * @return mixed|string
     */
    public function requestPaymentSession($domain, $validationUrl)
    {
        # attention!!!
        # for the payment session request with apple 
        # we must ALWAYS use the live api key
        # the test will never work!!!
        $responseString = $this->clientLive->wallets->requestApplePayPaymentSession(
            $domain,
            $validationUrl
        );

        return (string)$responseString;
    }

    /**
     * @param string $token
     */
    public function setPaymentToken($token)
    {
        $this->session->offsetSet(self::KEY_SESSION_PAYMENTTOKEN, $token);
    }

    /**
     * @return string
     */
    public function getPaymentToken()
    {
        return $this->session->offsetGet(self::KEY_SESSION_PAYMENTTOKEN);
    }

}
