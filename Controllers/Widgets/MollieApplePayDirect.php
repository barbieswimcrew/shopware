<?php

use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use MollieShopware\Components\ApplePayDirect\ApplePayDirect;
use MollieShopware\Components\Logger;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class Shopware_Controllers_Widgets_MollieApplePayDirect extends Shopware_Controllers_Frontend_Checkout
{

    /**
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Zend_Db_Adapter_Exception
     */
    public function addProductAction()
    {
        $basket = $this->basket;
        $admin = $this->admin;

        // delete the cart,
        // to make sure that only the selected product is transferred to Apple Pay
        $basket->sDeleteBasket();

        $productNumber = $this->Request()->getParam('number');
        $productQuantity = $this->Request()->getParam('quantity');

        $basket->sAddArticle($productNumber, $productQuantity);

        // add potential discounts or surcharges to prevent an amount mismatch
        // on patching the new amount after the confirmation.
        // only necessary if the customer directly checks out from product detail page
        $countries = $admin->sGetCountryList();
        $admin->sGetPremiumShippingcosts(reset($countries));

        echo "";
        die();
    }

    /**
     *
     */
    public function getShippingsAction()
    {
        $applePay = new ApplePayDirect(Shopware()->Shop());

        $cart = $applePay->getApplePayCart($this->basket);


        $countryCode = $this->Request()->getParam('countryCode');
        $postalCode = $this->Request()->getParam('postalCode');

        $countries = $this->admin->sGetCountryList();

        $foundCountry = null;

        /** @var array $country */
        foreach ($countries as $country) {

            if (strtolower($country['iso']) === strtolower($countryCode)) {
                $foundCountry = $country;
                break;
            }
        }

        $dispatchMethods = array();

        if ($foundCountry !== null) {
            $dispatchMethods = $this->admin->sGetPremiumDispatches($foundCountry['id']);
        }

        $shippingMethods = array();

        /** @var array $method */
        foreach ($dispatchMethods as $method) {

            $shippingMethods[] = array(
                'identifier' => $method['id'],
                'label' => $method['name'],
                'detail' => $method['description'],
                'amount' => 0,
            );
        }


        $data = array(
            'cart' => $cart->toArray(),
            'shippingmethods' => $shippingMethods,
        );

        echo json_encode($data);
        die();
    }

    /**
     *
     */
    public function setShippingAction()
    {
        $shippingIdentifier = $this->Request()->getParam('identifier', '');


        $this->basket->sSYSTEM->_GET['sAddPremium'] = $shippingIdentifier;
        $this->basket->sInsertPremium();

        $applePay = new ApplePayDirect(Shopware()->Shop());

        $cart = $applePay->getApplePayCart($this->basket);

        $data = array(
            'cart' => $cart->toArray(),
            'id' => $shippingIdentifier,
        );

        echo json_encode($data);
        die();
    }

    /**
     *
     */
    public function restoreCartAction()
    {
        $basket = $this->basket;

        $basket->sDeleteBasket();

        echo "";
        die();
    }


    /**
     * @throws Exception
     */
    public function createPaymentSessionAction()
    {
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

        try {

            $applePay = new ApplePayDirect(Shopware()->Shop());

            /** @var \Mollie\Api\MollieApiClient $mollieApi */
            $mollieApi = $this->getMollieApi();

            $domain = Shopware()->Shop()->getHost();
            $validationUrl = (string)$this->Request()->getParam('validationUrl');

            $response = $applePay->requestPaymentSession($mollieApi, $domain, $validationUrl);

            echo $response;

        } catch (Exception $ex) {

            Logger::log('error', $ex->getMessage(), $ex);

            http_response_code(500);
            die();
        }
    }


    /**
     *
     */
    public function createPaymentAction()
    {
        $this->redirect('/checkout/confirm');
    }

    /**
     * # todo ist duplicate mit Mollie.php Controller
     * @param int $shopId
     *
     * @return \Mollie\Api\MollieApiClient
     * @throws Exception
     */
    private function getMollieApi($shopId = null)
    {
        /** @var MollieApiClient $apiClient */
        $apiClient = null;

        /** @var \MollieShopware\Components\MollieApiFactory $apiFactory */
        $apiFactory = Shopware()->Container()->get('mollie_shopware.api_factory');

        if ($apiFactory !== null) {
            try {
                $apiClient = $apiFactory->create($shopId);
            } catch (ApiException $e) {
                Logger::log(
                    'error',
                    'Could not create an API client.',
                    $e,
                    true
                );
            }
        }

        return $apiClient;
    }

}
