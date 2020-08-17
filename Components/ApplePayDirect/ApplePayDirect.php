<?php

namespace MollieShopware\Components\ApplePayDirect;

use Enlight_Controller_Request_Request;
use Enlight_View;
use Mollie\Api\MollieApiClient;
use MollieShopware\Components\ApplePayDirect\Models\Cart\ApplePayCart;
use MollieShopware\Components\ApplePayDirect\Models\ApplePayButton;
use MollieShopware\Components\Constants\PaymentMethod;
use MollieShopware\Components\Services\PaymentMethodService;
use MollieShopware\Components\Shipping\Shipping;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;
use Shopware_Components_Modules;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class ApplePayDirect implements ApplePayDirectInterface
{

    /**
     *
     */
    const KEY_MOLLIE_APPLEPAY_BUTTON = 'sMollieApplePayDirectButton';

    /**
     * @var \sAdmin
     */
    private $sAdmin;

    /**
     * @var Shipping $cmpShipping
     */
    private $cmpShipping;

    /**
     * @var \sBasket
     */
    private $sBasket;

    /**
     * @var PaymentMethodService $paymentMethodService
     */
    private $paymentMethodService;

    /**
     * @var \Enlight_Components_Session_Namespace
     */
    private $session;


    /**
     * @param Shopware_Components_Modules $modules
     * @param Shipping $cmpShipping
     * @param PaymentMethodService $paymentMethodService
     * @param $session
     */
    public function __construct($modules, Shipping $cmpShipping, PaymentMethodService $paymentMethodService, $session)
    {
        $this->sAdmin = $modules->Admin();
        $this->sBasket = $modules->Basket();

        $this->cmpShipping = $cmpShipping;
        $this->paymentMethodService = $paymentMethodService;
        $this->session = $session;
    }


    /**
     * @param Shop $shop
     * @param $country
     * @return mixed|ApplePayCart
     * @throws \Enlight_Exception
     */
    public function getApplePayCart(Shop $shop, $country)
    {
        $cart = new ApplePayCart(
            'DE', # todo country, von wo?
            $shop->getCurrency()->getCurrency()
        );

        /** @var array $item */
        foreach ($this->sBasket->sGetBasketData()['content'] as $item) {
            $cart->addItem(
                $item['ordernumber'],
                $item['articlename'],
                (int)$item['quantity'],
                (float)$item['priceNumeric']
            );
        }

        /** @var array $shipping */
        $shipping = $this->sAdmin->sGetPremiumShippingcosts($country);

        if ($shipping['value'] !== null && $shipping['value'] > 0) {

            /** @var array $shipmentMethod */
            $shipmentMethod = $this->cmpShipping->getCartShippingMethod();

            $cart->setShipping($shipmentMethod['name'], (float)$shipping['value']);
        }

        # if we are on PDP then our apple pay label and amount
        # is the one from our article
        $cart->setLabel($shop->getName());

        return $cart;
    }

    /**
     * @param Enlight_Controller_Request_Request $request
     * @param Enlight_View $view
     * @param Shop $shop
     */
    public function addButtonStatus(Enlight_Controller_Request_Request $request, Enlight_View $view, Shop $shop)
    {
        /** @var string $controller */
        $controller = strtolower($request->getControllerName());

        $country = 'DE'; # todo country, von wo?;

        $button = new ApplePayButton(
            $this->isApplePayDirectAvailable(),
            $country,
            $shop->getCurrency()->getCurrency()
        );

        if ($controller === 'detail') {
            $vars = $view->getAssign();
            $button->setItemMode($vars["sArticle"]["ordernumber"]);
        }

        $view->assign(self::KEY_MOLLIE_APPLEPAY_BUTTON, $button->toArray());
    }

    /**
     * @param MollieApiClient $client
     * @param string $domain
     * @param string $validationUrl
     * @return string
     */
    public function requestPaymentSession(MollieApiClient $client, $domain, $validationUrl)
    {
        $responseString = $client->wallets->requestApplePayPaymentSession($domain, $validationUrl);

        return (string)$responseString;
    }

    /**
     * @param $docRoot
     * @return mixed|void
     */
    public function downloadDomainAssociationFile($docRoot)
    {
        $content = file_get_contents('https://www.mollie.com/.well-known/apple-developer-merchantid-domain-association');

        $appleFolder = $docRoot . '/.well-known';

        if (!file_exists($appleFolder)) {
            mkdir($appleFolder);
        }

        file_put_contents($appleFolder . '/apple-developer-merchantid-domain-association', $content);
    }

    /**
     * @return Payment
     * @throws \Exception
     */
    public function getPaymentMethod()
    {
        $applePayDirect = $this->paymentMethodService->getPaymentMethod(
            [
                'name' => ApplePayDirectInterface::APPLEPAY_DIRECT_NAME,
                'active' => true,
            ]
        );

        if ($applePayDirect instanceof Payment) {
            return $applePayDirect;
        }

        throw new \Exception('Apple Pay Direct Payment not found');
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


    /**
     * @return bool
     */
    private function isApplePayDirectAvailable(): bool
    {
        try {
            $this->getPaymentMethod();
            return true;
        } catch (\Exception $ex) {
            return false;
        }
    }

}
