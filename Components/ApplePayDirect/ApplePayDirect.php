<?php

namespace MollieShopware\Components\ApplePayDirect;

use Enlight_Controller_Request_Request;
use Enlight_View;
use Mollie\Api\MollieApiClient;
use MollieShopware\Components\ApplePayDirect\Models\ApplePayCart;
use MollieShopware\Components\ApplePayDirect\Models\ApplePayButton;
use MollieShopware\Components\Constants\PaymentMethod;
use MollieShopware\Components\Services\PaymentMethodService;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class ApplePayDirect
{

    const APPLEPAY_DIRECT_NAME = 'mollie_' . PaymentMethod::APPLEPAY_DIRECT;

    const KEY_MOLLIE_APPLEPAY_BUTTON = 'sMollieApplePayDirectButton';


    /**
     * @var Shop
     */
    private $shop;


    /**
     * @param Shop $shop
     */
    public function __construct(Shop $shop)
    {
        $this->shop = $shop;
    }


    /**
     * @param \sBasket $basket
     * @return ApplePayCart
     * @throws \Enlight_Exception
     */
    public function getApplePayCart(\sBasket $basket)
    {
        $cart = new ApplePayCart(
            'DE', # todo country, von wo?
            $this->shop->getCurrency()->getCurrency()
        );

        /** @var array $item */
        foreach ($basket->sGetBasketData()['content'] as $item) {
            $cart->addItem(
                $item['ordernumber'],
                $item['articlename'],
                (int)$item['quantity'],
                $item['price']
            );
        }

        # if we are on PDP then our apple pay label and amount
        # is the one from our article
        $cart->setLabel($this->shop->getName());

        return $cart;
    }


    /**
     * @param Enlight_Controller_Request_Request $request
     * @param Enlight_View $view
     */
    public function addButtonStatus(Enlight_Controller_Request_Request $request, Enlight_View $view)
    {
        /** @var string $controller */
        $controller = $request->getControllerName();

        $country = 'DE'; # todo country, von wo?;

        $button = new ApplePayButton(
            $this->isApplePayDirectAvailable(),
            $country,
            $this->shop->getCurrency()->getCurrency()
        );

        switch (strtolower($controller)) {
            case 'detail':
                $vars = $view->getAssign();
                $button->setItemMode($vars["sArticle"]["ordernumber"]);
                break;
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
     * @return bool
     */
    private function isApplePayDirectAvailable(): bool
    {
        /** @var PaymentMethodService $paymentMethodService */
        $paymentMethodService = Shopware()->Container()->get('mollie_shopware.payment_method_service');

        $applePayDirect = $paymentMethodService->getPaymentMethod(
            [
                'name' => self::APPLEPAY_DIRECT_NAME,
                'active' => true,
            ]
        );

        return ($applePayDirect instanceof Payment);
    }

}
