<?php

namespace MollieShopware\Components\ApplePayDirect;

use Enlight_Controller_Request_Request;
use Enlight_View;
use MollieShopware\Components\ApplePayDirect\Models\ApplePayButton;
use MollieShopware\Components\Country\CountryIsoParser;
use MollieShopware\Components\Services\PaymentMethodService;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class ApplePayDirectSetup
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
     * @var PaymentMethodService $paymentMethodService
     */
    private $paymentMethodService;


    /**
     * ApplePayDirect constructor.
     *
     * @param $modules
     * @param PaymentMethodService $paymentMethodService
     */
    public function __construct($modules, PaymentMethodService $paymentMethodService)
    {
        $this->sAdmin = $modules->Admin();
        $this->paymentMethodService = $paymentMethodService;
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

        $activeCountries = $this->sAdmin->sGetCountryList();
        $firstCountry = array_shift($activeCountries);

        $isoParser = new CountryIsoParser();

        $country = $isoParser->getISO($firstCountry);

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
                'name' => ApplePayDirectHandlerInterface::APPLEPAY_DIRECT_NAME,
                'active' => true,
            ]
        );

        if ($applePayDirect instanceof Payment) {
            return $applePayDirect;
        }

        throw new \Exception('Apple Pay Direct Payment not found');
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
