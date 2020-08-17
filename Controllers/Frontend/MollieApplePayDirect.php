<?php

use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use MollieShopware\Components\ApplePayDirect\ApplePayDirect;
use MollieShopware\Components\ApplePayDirect\ApplePayDirectInterface;
use MollieShopware\Components\Country\CountryIsoParser;
use MollieShopware\Components\Logger;
use MollieShopware\Components\Order\OrderSession;
use MollieShopware\Components\Shipping\Shipping;
use MollieShopware\Traits\MollieApiClientTrait;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\CSRFWhitelistAware;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
class Shopware_Controllers_Frontend_MollieApplePayDirect extends Shopware_Controllers_Frontend_Checkout implements CSRFWhitelistAware
{

    use MollieApiClientTrait;


    /**
     * @return string[]
     */
    public function getWhitelistedCSRFActions()
    {
        return [
            # todo, vl nicht so gut ha :D
            'startPayment',
            'finishPayment',
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
        try {
            Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

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

        } catch (Throwable $ex) {
            Logger::log('error when adding product to apple pay cart', $ex->getMessage(), $ex);

            http_response_code(500);
            die();
        }
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
        try {
            Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

            /** @var ApplePayDirectInterface $applePay */
            $applePay = Shopware()->Container()->get('mollie_shopware.applepay_direct_service');
            /** @var Shipping $shipping */
            $shipping = Shopware()->Container()->get('mollie_shopware.components.shipping');


            /** @var string $countryCode */
            $countryCode = $this->Request()->getParam('countryCode');

            /** @var array $userCountry */
            $userCountry = $this->getCountry($countryCode);

            if ($userCountry === null) {
                throw new Exception('Country ' . $countryCode . ' is not supported and active.');
            }

            # set the current country in our session
            # to the one, from the apple pay sheet.
            $this->session->offsetSet('sCountry', $userCountry['id']);

            /** @var int $applePayMethodId */
            $applePayMethodId = $applePay->getPaymentMethod()->getId();

            # get all available shipping methods
            # for apple pay direct and our selected country
            $dispatchMethods = $shipping->getShippingMethods($userCountry['id'], $applePayMethodId);

            # now build an apple pay conform array
            # of these shipping methods
            $shippingMethods = $this->formatApplePayShippingMethods($dispatchMethods, $userCountry, $shipping);


            /** @var ContainerAwareEventManager $eventManager */
            $eventManager = Shopware()->Container()->get('events');

            # fire event about the shipping methods that
            # will be returned for the country
            $eventManager->filter(
                'Mollie_ApplePayDirect_getShippings_FilterResult',
                $shippingMethods,
                array(
                    'country' => $countryCode
                )
            );


            $data = array(
                'success' => true,
                'cart' => $this->getCart()->toArray(),
                'shippingmethods' => $shippingMethods,
            );

            echo json_encode($data);
            die();

        } catch (Throwable $ex) {
            Logger::log('error when loading apple pay shipping methods', $ex->getMessage(), $ex);

            $data = array(
                'success' => false,
            );

            echo json_encode($data);
            die();
        }
    }

    /**
     * This route sets the provided shipping method
     * as the one that will be used for the cart.
     * It then returns the cart as JSON along
     * with the used shipping identifier.
     */
    public function setShippingAction()
    {
        try {
            Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

            $shippingIdentifier = $this->Request()->getParam('identifier', '');

            /** @var ContainerAwareEventManager $eventManager */
            $eventManager = Shopware()->Container()->get('events');

            # fire event about the shipping methods that
            # will be set for the user
            $eventManager->filter(
                'Mollie_ApplePayDirect_setShipping_FilterResult',
                $shippingIdentifier,
                array()
            );


            if (!empty($shippingIdentifier)) {
                $shipping = Shopware()->Container()->get('mollie_shopware.components.shipping');
                $shipping->setCartShippingMethodID($shippingIdentifier);
            }

            $data = array(
                'success' => false,
                'cart' => $this->getCart()->toArray(),
            );

            echo json_encode($data);
            die();

        } catch (Throwable $ex) {
            Logger::log('error when setting apple pay shipping', $ex->getMessage(), $ex);

            $data = array(
                'success' => false,
            );

            echo json_encode($data);
            die();
        }
    }

    /**
     * This route restores the cart and
     * adds all items again that where previously
     * added before starting Apple Pay.
     */
    public function restoreCartAction()
    {
        try {
            Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

            $basket = $this->basket;

            $basket->sDeleteBasket();

            echo "";
            die();

        } catch (Throwable $ex) {
            Logger::log('error when restoring apple pay cart', $ex->getMessage(), $ex);

            http_response_code(500);
            die();
        }
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
    public function startPaymentAction()
    {
        Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

        try {
            /** @var ApplePayDirectInterface $applePay */
            $applePay = Shopware()->Container()->get('mollie_shopware.applepay_direct_service');


            $email = $this->Request()->getParam('email', '');
            $firstname = $this->Request()->getParam('firstname', '');
            $lastname = $this->Request()->getParam('lastname', '');

            $street = $this->Request()->getParam('street', '');
            $zipcode = $this->Request()->getParam('postalCode', '');
            $city = $this->Request()->getParam('city', '');
            $countryCode = $this->Request()->getParam('countryCode', '');

            /** @var array $country */
            $country = $this->getCountry($countryCode);

            if ($country === null) {
                throw new Exception('No Country found for code ' . $countryCode);
            }

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
                $country['id']
            );

            # now load the guest account
            # and set the id as "current" user for the next request
            $guest = $account->getGuestAccount($email);
            $this->session->offsetSet('sUserId', $guest['id']);

            # save our payment token
            # that will be used when creating the
            # payment in the mollie controller action
            $paymentToken = $this->Request()->getParam('paymentToken', '');
            $applePay->setPaymentToken($paymentToken);

            # redirect to our finish action
            # on that action the new guest user is fully loaded
            # into our view variables and we can continue with
            # preparing the order in our session and finishing the checkout
            $this->redirect(
                [
                    'controller' => 'MollieApplePayDirect',
                    'action' => 'finishPayment',
                ]
            );

        } catch (Throwable $ex) {

            Logger::log('error', $ex->getMessage(), $ex);

            http_response_code(500);
            die();
        }
    }

    /**
     * @throws Exception
     */
    public function finishPaymentAction()
    {
        try {

            Shopware()->Plugins()->Controller()->ViewRenderer()->setNoRender();

            /** @var ApplePayDirectInterface $applePay */
            $applePay = Shopware()->Container()->get('mollie_shopware.applepay_direct_service');

            $orderSession = Shopware()->Container()->get('mollie_shopware.components.order_session');

            /** @var \Shopware\Bundle\StoreFrontBundle\Struct\ShopContext $context */
            $context = $this->container->get('shopware_storefront.context_service')->getShopContext();

            $orderSession->prepareOrderSession($this, $applePay->getPaymentMethod(), $context);

            # redirect to our centralized mollie
            # direct controller action
            $this->redirect(
                [
                    'controller' => 'Mollie',
                    'action' => 'direct',
                ]
            );

        } catch (Throwable $ex) {

            Logger::log('error', $ex->getMessage(), $ex);

            http_response_code(500);
            die();
        }
    }

    /**
     * @return mixed
     */
    private function getCart()
    {
        /** @var ApplePayDirectInterface $applePay */
        $applePay = Shopware()->Container()->get('mollie_shopware.applepay_direct_service');

        return $applePay->getApplePayCart(Shopware()->Shop());
    }

    /**
     * @param $countryCode
     * @return array|null
     */
    private function getCountry($countryCode)
    {
        $countries = $this->admin->sGetCountryList();

        $foundCountry = null;

        $isoParser = new CountryIsoParser();

        /** @var array $country */
        foreach ($countries as $country) {

            /** @var string $iso */
            $iso = $isoParser->getISO($country);

            if (strtolower($iso) === strtolower($countryCode)) {
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
