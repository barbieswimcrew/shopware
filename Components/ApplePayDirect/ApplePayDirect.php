<?php

namespace MollieShopware\Components\ApplePayDirect;

use Enlight_Controller_Request_Request;
use Enlight_View;
use Mollie\Api\MollieApiClient;
use MollieShopware\Components\ApplePayDirect\Models\Cart\ApplePayCart;
use MollieShopware\Components\ApplePayDirect\Models\ApplePayButton;
use MollieShopware\Components\Constants\PaymentMethod;
use MollieShopware\Components\Services\PaymentMethodService;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

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
     * @param \sBasket $basket
     * @param \sAdmin $admin
     * @param Shop $shop
     * @return mixed|ApplePayCart
     * @throws \Enlight_Exception
     */
    public function getApplePayCart(\sBasket $basket, \sAdmin $admin, Shop $shop, $country)
    {
        $cart = new ApplePayCart(
            'DE', # todo country, von wo?
            $shop->getCurrency()->getCurrency()
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

        /** @var array $shipping */
        $shipping = $admin->sGetPremiumShippingcosts($country);

        if ($shipping['value'] > 0) {
            $cart->addItem(
                'SHIP1',
                'Shipping',
                1,
                (float)$shipping['value']
            );
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
        $controller = $request->getControllerName();

        $country = 'DE'; # todo country, von wo?;

        $button = new ApplePayButton(
            $this->isApplePayDirectAvailable(),
            $country,
            $shop->getCurrency()->getCurrency()
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
     * @param \sAdmin $admin
     * @return int|string
     * @throws \Exception
     */
    public function getPaymentMethodID(\sAdmin $admin)
    {
        $means = $admin->sGetPaymentMeans();

        foreach ($means as $paymentID => $payment) {

            if ($payment['name'] === ApplePayDirectInterface::APPLEPAY_DIRECT_NAME) {
                return $paymentID;
            }
        }

        throw new \Exception('Apple Pay Direct Payment not found');
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
                'name' => ApplePayDirectInterface::APPLEPAY_DIRECT_NAME,
                'active' => true,
            ]
        );

        return ($applePayDirect instanceof Payment);
    }

}
