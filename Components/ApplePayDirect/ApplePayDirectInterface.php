<?php

namespace MollieShopware\Components\ApplePayDirect;

use Enlight_Controller_Request_Request;
use Enlight_View;
use Mollie\Api\MollieApiClient;
use Shopware\Models\Shop\Shop;

/**
 * @copyright 2020 dasistweb GmbH (https://www.dasistweb.de)
 */
interface ApplePayDirectInterface
{

    /**
     * @param \sBasket $basket
     * @param Shop $shop
     * @return mixed
     */
    public function getApplePayCart(\sBasket $basket, \sAdmin $admin, Shop $shop);

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

}
