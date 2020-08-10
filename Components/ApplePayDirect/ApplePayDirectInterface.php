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
interface ApplePayDirectInterface
{

    /**
     *
     */
    const APPLEPAY_DIRECT_NAME = 'mollie_' . PaymentMethod::APPLEPAY_DIRECT;

    /**
     * @param Shop $shop
     * @param $country
     * @return mixed
     */
    public function getApplePayCart(Shop $shop, $country);

    /**
     * @param Enlight_Controller_Request_Request $request
     * @param Enlight_View $view
     * @param Shop $shop
     * @return mixed
     */
    public function addButtonStatus(Enlight_Controller_Request_Request $request, Enlight_View $view, Shop $shop);

    /**
     * @param MollieApiClient $client
     * @param $domain
     * @param $validationUrl
     * @return mixed
     */
    public function requestPaymentSession(MollieApiClient $client, $domain, $validationUrl);

    /**
     * @param $docRoot
     * @return mixed
     */
    public function downloadDomainAssociationFile($docRoot);

    /**
     * @param \sAdmin $admin
     * @return mixed
     */
    public function getPaymentMethodID(\sAdmin $admin);

    /**
     * @param $token
     * @return mixed
     */
    public function setPaymentToken($token);

    /**
     * @return string
     */
    public function getPaymentToken();

}
