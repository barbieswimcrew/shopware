<?php

use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use MollieShopware\Components\ApplePayDirect\ApplePayDirect;
use MollieShopware\Components\ApplePayDirect\ApplePayDirectInterface;
use MollieShopware\Components\Logger;
use MollieShopware\Components\Shipping\Shipping;
use Shopware\Components\CSRFWhitelistAware;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class Shopware_Controllers_Frontend_MollieApplePayDirect extends Shopware_Controllers_Frontend_Checkout implements CSRFWhitelistAware
{

    /**
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            'createPayment' # todo, vl nicht so gut ha :D
        ];
    }

    /**
     * This route adds the provided article
     * to the cart.
     * It will first create a snapshot (todo) of the current
     * cart, then it will delete it and only add our single product to it.
     *
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
     * This route returns a JSON with the current
     * cart and all available shipping methods for the
     * provided country.
     * The shipping methods have to be configured for
     * Apple Pay Direct and the country.
     * The code will also lookup the shipping costs for each method.
     */
    public function getShippingsAction()
    {
        /** @var ApplePayDirectInterface $applePay */
        $applePay = Shopware()->Container()->get('mollie_shopware.applepay_direct_service');

        $shippingMethods = array();

        /** @var string $countryCode */
        $countryCode = $this->Request()->getParam('countryCode');

        /** @var array $userCountry */
        $userCountry = $this->getCountry($countryCode);

        if ($userCountry !== null) {
            /** @var int $applePayMethodId */
            $applePayMethodId = $applePay->getPaymentMethodID($this->admin);

            # get all available shipping methods
            # for apple pay direct and our selected country
            $shipping = new Shipping($this->admin, $this->session);
            $dispatchMethods = $shipping->getShippingMethods($userCountry['id'], $applePayMethodId);

            # now build an apple pay conform array
            # of these shipping methods
            $shippingMethods = $this->formatApplePayShippingMethods($dispatchMethods, $userCountry, $shipping);
        }

        $data = array(
            'cart' => $this->getCart()->toArray(),
            'shippingmethods' => $shippingMethods,
        );

        echo json_encode($data);
        die();
    }

    /**
     * This route sets the provided shipping method
     * as the one that will be used for the cart.
     * It then returns the cart as JSON along
     * with the used shipping identifier.
     */
    public function setShippingAction()
    {
        $shippingIdentifier = $this->Request()->getParam('identifier', '');

        if (!empty($shippingIdentifier)) {
            $shipping = new Shipping($this->admin, $this->session);
            $shipping->setCartShippingMethodID($shippingIdentifier);
        }

        $data = array(
            'cart' => $this->getCart()->toArray(),
            'id' => $shippingIdentifier,
        );

        echo json_encode($data);
        die();
    }

    /**
     * This route restores the cart and
     * adds all items again that where previously
     * added before starting Apple Pay.
     */
    public function restoreCartAction()
    {
        $basket = $this->basket;

        $basket->sDeleteBasket();

        echo "";
        die();
    }

    /**
     * This route starts a new merchant validation that
     * is required to start an apple pay session checkout.
     * It will use Mollie as proxy to talk to Apple.
     * The resulting session data must then be output
     * exactly as it has been received.
     *
     * @return mixed
     * @throws Exception
     */
    public function createPaymentSessionAction()
    {
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

        try {

            /** @var ApplePayDirectInterface $applePay */
            $applePay = Shopware()->Container()->get('mollie_shopware.applepay_direct_service');

            /** @var \Mollie\Api\MollieApiClient $mollieApi */
            $mollieApi = $this->getMollieApi();

            $domain = Shopware()->Shop()->getHost();
            $validationUrl = (string)$this->Request()->getParam('validationUrl');

            $response = $applePay->requestPaymentSession(
                $mollieApi,
                $domain,
                $validationUrl
            );

            echo $response;

        } catch (Exception $ex) {

            Logger::log('error', $ex->getMessage(), $ex);

            http_response_code(500);
            die();
        }
    }

    /**
     * This route is the last part of processing an apple pay direct payment.
     * It will receive the payment token from the client
     * and continue with the server side checkout process.
     *
     * @throws Exception
     */
    public function createPaymentAction()
    {
        /** @var ApplePayDirectInterface $applePay */
        $applePay = Shopware()->Container()->get('mollie_shopware.applepay_direct_service');



        $email = $this->Request()->getParam('email', '');
        $firstname = $this->Request()->getParam('firstname', '');
        $lastname = $this->Request()->getParam('lastname', '');

        $street = $this->Request()->getParam('street', '');
        $zipcode = $this->Request()->getParam('postalCode', '');
        $city = $this->Request()->getParam('city', '');
        $countryCode = $this->Request()->getParam('countryCode', '');

        $phone = $this->Request()->getParam('phone', '');

        /** @var array $country */
        $country = $this->getCountry($countryCode);


        $account = new MollieShopware\Components\Account\Account(
            $this->admin,
            $this->session,
            $this->container->get('passwordencoder'),
            $this->container->get('mollie_shopware.components.apple_pay_direct.gateway.dbal.register_guest_customer_gateway')
        );

        $account->createGuestAccount(
            $email,
            $firstname,
            $lastname,
            $street,
            $zipcode,
            $city,
            $country['id'],
            $phone
        );


        $guest = $account->getGuestAccount($email);


        $sOrderVariables = $this->getBasket(false);

        $basket = $this->getBasket(false);
        $sOrderVariables['sBasketView'] = $basket;
        $sOrderVariables['sBasket'] = $basket;
        $sOrderVariables['sUserData'] = $this->View()->getAssign('sUserData');

        # set to payment method "apple pay direct"
        $sOrderVariables['sUserData'] ['additional']['user']['paymentID'] = $applePay->getPaymentMethodID($this->admin);
        $sOrderVariables['sUserData'] ['additional']['payment'] = $applePay->getPaymentMethod($this->admin);


        $sOrderVariables = new ArrayObject($sOrderVariables, ArrayObject::ARRAY_AS_PROPS);
        

        $this->session->offsetSet('sOrderVariables', $sOrderVariables);
        $this->session->offsetSet('sUserId', $guest['id']);


        # save our payment token
        # that will be used when creating the
        # payment in the mollie controller action
        $paymentToken = $this->Request()->getParam('paymentToken', '');


        $applePay->setPaymentToken($paymentToken);


        # redirect to our centralized mollie
        # direct controller action
        $this->redirect('/Mollie/direct');
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

    /**
     * @return mixed
     */
    private function getCart()
    {
        /** @var ApplePayDirectInterface $applePay */
        $applePay = Shopware()->Container()->get('mollie_shopware.applepay_direct_service');

        $cart = $applePay->getApplePayCart(
            Shopware()->Shop(),
            $this->getCountry('DE') # todo
        );

        return $cart;
    }

    /**
     * @param $countryCode
     * @return array|null
     */
    private function getCountry($countryCode)
    {
        $countries = $this->admin->sGetCountryList();

        $foundCountry = null;

        /** @var array $country */
        foreach ($countries as $country) {
            if (array_key_exists('iso', $country) && strtolower($country['iso']) === strtolower($countryCode)) {
                $foundCountry = $country;
                break;
            }

            if (array_key_exists('countryiso', $country) && strtolower($country['countryiso']) === strtolower($countryCode)) {
                $foundCountry = $country;
                break;
            }
        }

        return $foundCountry;
    }

    /**
     * @param array $dispatchMethods
     * @param $userCountry
     * @return array
     */
    private function formatApplePayShippingMethods(array $dispatchMethods, $userCountry, Shipping $shipping)
    {
        $selectedMethod = null;
        $otherMethods = array();

        $selectedMethodID = $shipping->getCartShippingMethodID();

        /** @var array $method */
        foreach ($dispatchMethods as $method) {

            if ($selectedMethodID === $method['id']) {
                $selectedMethod = array(
                    'identifier' => $method['id'],
                    'label' => $method['name'],
                    'detail' => $method['description'],
                    'amount' => $shipping->getShippingMethodCosts($userCountry, $method['id'])
                );
            } else {
                $otherMethods[] = array(
                    'identifier' => $method['id'],
                    'label' => $method['name'],
                    'detail' => $method['description'],
                    'amount' => $shipping->getShippingMethodCosts($userCountry, $method['id'])
                );
            }
        }

        $shippingMethods = array();

        if ($selectedMethod !== null) {
            $shippingMethods[] = $selectedMethod;
        } else {
            # set first one as default
            foreach ($otherMethods as $method) {
                $shipping->setCartShippingMethodID($method['identifier']);
                break;
            }
        }

        foreach ($otherMethods as $method) {
            $shippingMethods[] = $method;
        }

        return $shippingMethods;
    }

}
