<?php

namespace MollieShopware\Components\ApplePayDirect;

use Enlight_Controller_Request_Request;
use Enlight_View;
use Mollie\Api\MollieApiClient;
use MollieShopware\Components\Constants\PaymentMethod;
use Shopware\Models\Shop\Shop;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
interface ApplePayDirectHandlerInterface
{

    /**
     *
     */
    const APPLEPAY_DIRECT_NAME = 'mollie_' . PaymentMethod::APPLEPAY_DIRECT;

    /**
     * @return mixed
     */
    public function getApplePayCart();

    /**
     * @param $domain
     * @param $validationUrl
     * @return mixed
     */
    public function requestPaymentSession($domain, $validationUrl);

    /**
     * @param $token
     * @return mixed
     */
    public function setPaymentToken($token);

    /**
     * @return mixed
     */
    public function getPaymentToken();

}
