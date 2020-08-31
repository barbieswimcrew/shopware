<?php

namespace MollieShopware\Components\ApplePayDirect\Services;

use Enlight_Controller_Request_Request;
use Enlight_View;
use MollieShopware\Components\ApplePayDirect\Models\ApplePayButton;
use MollieShopware\Components\Country\CountryIsoParser;
use Shopware\Models\Shop\Shop;

class ApplePayButtonBuilder
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
     * @var ApplePayPaymentMethod
     */
    private $applePayPaymentMethod;


    /**
     * ApplePayButtonBuilder constructor.
     *
     * @param $modules
     * @param ApplePayPaymentMethod $applePayPaymentMethod
     */
    public function __construct($modules, ApplePayPaymentMethod $applePayPaymentMethod)
    {
        $this->sAdmin = $modules->Admin();
        $this->applePayPaymentMethod = $applePayPaymentMethod;
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
            $this->applePayPaymentMethod->isApplePayDirectEnabled(),
            $country,
            $shop->getCurrency()->getCurrency()
        );

        if ($controller === 'detail') {
            $vars = $view->getAssign();
            $button->setItemMode($vars["sArticle"]["ordernumber"]);
        }

        $view->assign(self::KEY_MOLLIE_APPLEPAY_BUTTON, $button->toArray());
    }

}
