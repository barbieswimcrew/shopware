<?php

namespace MollieShopware\Components\ApplePayDirect;

use Enlight_Controller_Request_Request;
use Enlight_View;
use Mollie\Api\MollieApiClient;
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
     * @param Enlight_Controller_Request_Request $request
     * @param Enlight_View $view
     */
    public function addViewData(Enlight_Controller_Request_Request $request, Enlight_View $view)
    {
        /** @var string $controller */
        $controller = $request->getControllerName();


        $applePayData = new ApplePayViewData(
            $this->isApplePayDirectAvailable(),
            'DE', # todo country, von wo?
            $this->shop->getCurrency()->getCurrency()
        );


        switch (strtolower($controller)) {
            case 'detail':
                $vars = $view->getAssign();

                $applePayData->setMode('item');

                $applePayData->addItem(
                    $vars["sArticle"]["ordernumber"],
                    $vars["sArticle"]["articleName"],
                    1,
                    $vars["sArticle"]["price_numeric"]
                );

                # if we are on PDP then our apple pay label and amount
                # is the one from our article
                $applePayData->setLabel($vars["sArticle"]["articleName"]);

                break;
        }

        $view->assign('sMollieApplePayDirect', $applePayData->toArray());
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
