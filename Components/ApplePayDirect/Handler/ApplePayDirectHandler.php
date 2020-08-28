<?php

namespace MollieShopware\Components\ApplePayDirect\Handler;

use Enlight_Controller_Request_Request;
use Enlight_View;
use Mollie\Api\MollieApiClient;
use MollieShopware\Components\ApplePayDirect\ApplePayDirectHandlerInterface;
use MollieShopware\Components\ApplePayDirect\Models\Cart\ApplePayCart;
use MollieShopware\Components\ApplePayDirect\Models\ApplePayButton;
use MollieShopware\Components\Constants\PaymentMethod;
use MollieShopware\Components\Country\CountryIsoParser;
use MollieShopware\Components\Services\PaymentMethodService;
use MollieShopware\Components\Shipping\Shipping;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;
use Shopware_Components_Modules;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class ApplePayDirectHandler implements ApplePayDirectHandlerInterface
{

    /**
     *
     */
    const KEY_MOLLIE_APPLEPAY_BUTTON = 'sMollieApplePayDirectButton';


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
    private $sAdmin;

    /**
     * @var \sBasket
     */
    private $sBasket;

    /**
     * @var Shipping $cmpShipping
     */
    private $cmpShipping;

    /**
     * @var PaymentMethodService $paymentMethodService
     */
    private $paymentMethodService;

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
     * @param PaymentMethodService $paymentMethodService
     * @param $session
     */
    public function __construct(MollieApiClient $clientLive, MollieApiClient $clientTest, bool $isTestModeEnabled, $sAdmin, $sBasket, Shipping $cmpShipping, PaymentMethodService $paymentMethodService, $session)
    {
        $this->clientLive = $clientLive;
        $this->clientTest = $clientTest;
        $this->isTestModeEnabled = $isTestModeEnabled;

        $this->sAdmin = $sAdmin;
        $this->sBasket = $sBasket;
        $this->cmpShipping = $cmpShipping;
        $this->paymentMethodService = $paymentMethodService;
        $this->session = $session;
    }


    /**
     * @param Shop $shop
     * @return mixed|ApplePayCart
     * @throws \Enlight_Exception
     */
    public function getApplePayCart(Shop $shop)
    {
        $cart = new ApplePayCart();

        $taxes = 0;

        /** @var array $item */
        foreach ($this->sBasket->sGetBasketData()['content'] as $item) {
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
        $country = $this->sAdmin->sGetUserData()['additional']['country'];

        /** @var array $shipping */
        $shipping = $this->sAdmin->sGetPremiumShippingcosts($country);

        if ($shipping['value'] !== null && $shipping['value'] > 0) {

            /** @var array $shipmentMethod */
            $shipmentMethod = $this->cmpShipping->getCartShippingMethod();

            $cart->setShipping($shipmentMethod['name'], (float)$shipping['value']);

            # todo refactor + tests
            $taxes += ($shipping['brutto'] - $shipping['netto']);
        }


        # todo translation
        $cart->setTaxes("TAXES", $taxes);

        # if we are on PDP then our apple pay label and amount
        # is the one from our article
        $cart->setLabel($shop->getName());

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
        $this->session->offsetSet('MOLLIE_APPLEPAY_PAYENTTOKEN', $token);
    }

    /**
     * @return string
     */
    public function getPaymentToken()
    {
        return $this->session->offsetGet('MOLLIE_APPLEPAY_PAYENTTOKEN');
    }

}
